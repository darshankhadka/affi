<?php

namespace App\Services\Ingestion;

use App\Models\AffiliateProgramme;
use App\Models\AffiliateProvider;
use App\Models\AutomationJob;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\AwinDatafeedService;
use App\Services\Affiliate\AwinProvider;
use App\Services\Affiliate\CjProvider;
use App\Support\SecretRedactor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BulkIngestionService
{
    public function __construct(
        protected ProductIngestionService $ingestionService,
        protected CjProvider $cjProvider,
        protected AwinProvider $awinProvider,
        protected AwinDatafeedService $awinDatafeedService
    ) {}

    /**
     * Run high-volume, resumable CJ bulk ingestion with pagination and checkpoints.
     *
     * @param array{
     *   market: string,
     *   max_products?: int,
     *   batch_size?: int,
     *   partner_id?: ?string,
     *   keywords?: ?string,
     *   job_id?: ?int,
     *   resume?: bool,
     *   dry_run?: bool
     * } $options
     * @param ?callable $progressCallback
     * @return array
     */
    public function ingestCjBulk(array $options, ?callable $progressCallback = null): array
    {
        $startTime = microtime(true);
        $provider = AffiliateProvider::where('code', 'cj')->firstOrFail();
        $marketCode = strtolower($options['market'] ?? 'us');
        $market = Market::where('code', $marketCode)->firstOrFail();

        if (!$this->cjProvider->isConnected($provider)) {
            throw new RuntimeException('CJ Affiliate is not configured or missing API credentials.');
        }

        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.cj.api_token');
        $companyId = (string) ($config['company_id'] ?? config('services.cj.company_id'));

        $maxProducts = min((int) ($options['max_products'] ?? 100), 500);
        $batchSize = min((int) ($options['batch_size'] ?? 50), 50);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $resume = (bool) ($options['resume'] ?? false);

        // Resolve target partner IDs (approved / joined advertisers)
        $partnerId = $options['partner_id'] ?? null;
        if ($partnerId) {
            $partnerIds = [(string) $partnerId];
        } else {
            // Get all approved CJ programmes from database or joined advertisers
            $approved = AffiliateProgramme::where('provider_id', $provider->id)
                ->where('status', 'approved')
                ->pluck('external_programme_id')
                ->toArray();

            $partnerIds = !empty($approved) ? $approved : [];
        }

        // Create or retrieve AutomationJob record
        $job = null;
        $offset = 0;
        if (!empty($options['job_id'])) {
            $job = AutomationJob::find($options['job_id']);
            if ($job && $resume) {
                $offset = (int) ($job->metadata['checkpoint_offset'] ?? 0);
            }
        }

        if (!$job) {
            $job = AutomationJob::create([
                'provider_id' => $provider->id,
                'market_id' => $market->id,
                'batch_type' => $dryRun ? 'cj_bulk_ingestion_dry_run' : 'cj_bulk_ingestion',
                'status' => 'processing',
                'total_items' => 0,
                'processed_items' => 0,
                'failed_items' => 0,
                'started_at' => now(),
                'metadata' => [
                    'provider' => 'cj',
                    'market' => $marketCode,
                    'max_products' => $maxProducts,
                    'batch_size' => $batchSize,
                    'partner_ids' => $partnerIds,
                    'checkpoint_offset' => $offset,
                ],
            ]);
        }

        $metrics = [
            'discovered' => 0,
            'eligible' => 0,
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'matched' => 0,
            'skipped' => 0,
            'failed' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        $hasMore = true;

        while ($hasMore && $metrics['imported'] < $maxProducts) {
            $fetchLimit = min($batchSize, $maxProducts - $metrics['imported']);

            // Fetch with retry on rate limit / transient network error
            $fetchResult = $this->fetchCjWithRetry(
                $companyId,
                $apiToken,
                $partnerIds,
                $market,
                $fetchLimit,
                $offset
            );

            $items = $fetchResult['items'] ?? [];
            $totalCount = $fetchResult['total'] ?? 0;
            $hasMore = $fetchResult['has_more'] ?? false;

            if ($metrics['discovered'] === 0) {
                $metrics['discovered'] = $totalCount;
                $job->update(['total_items' => min($totalCount, $maxProducts)]);
            }

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                if ($metrics['imported'] >= $maxProducts) {
                    break 2;
                }

                $metrics['eligible']++;
                $dto = $this->cjProvider->normalizeCjItem($item, $market);

                if (!$dto) {
                    $metrics['skipped']++;
                    continue;
                }

                if ($dryRun) {
                    $metrics['imported']++;
                    $metrics['matched']++;
                    continue;
                }

                try {
                    $res = $this->ingestionService->ingest($dto, $market);
                    if ($res['success']) {
                        $metrics['imported']++;
                        if ($res['action'] === 'created_product') {
                            $metrics['created']++;
                        } else {
                            $metrics['matched']++;
                            $metrics['updated']++;
                        }
                    } else {
                        $metrics['failed']++;
                        if (!empty($res['error'])) {
                            $metrics['errors'][] = SecretRedactor::sanitize($res['error']);
                        }
                    }
                } catch (Throwable $e) {
                    $metrics['failed']++;
                    $metrics['errors'][] = SecretRedactor::sanitize($e->getMessage());
                }
            }

            $offset += count($items);

            // Update checkpoint
            $job->update([
                'processed_items' => $metrics['imported'],
                'failed_items' => $metrics['failed'],
                'metadata' => array_merge($job->metadata ?? [], [
                    'checkpoint_offset' => $offset,
                    'metrics' => $metrics,
                ]),
            ]);

            if ($progressCallback) {
                $progressCallback([
                    'job_id' => $job->id,
                    'offset' => $offset,
                    'imported' => $metrics['imported'],
                    'created' => $metrics['created'],
                    'matched' => $metrics['matched'],
                    'failed' => $metrics['failed'],
                    'total_discovered' => $metrics['discovered'],
                    'elapsed_ms' => (int) round((microtime(true) - $startTime) * 1000),
                ]);
            }

            // Respect rate limits with brief pause between batches
            usleep(150000); // 150ms
        }

        $job->update([
            'status' => $metrics['failed'] > 0 && $metrics['imported'] === 0 ? 'failed' : 'completed',
            'finished_at' => now(),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'error_log' => !empty($metrics['errors']) ? implode("\n", array_slice($metrics['errors'], 0, 30)) : null,
            'metadata' => array_merge($job->metadata ?? [], [
                'final_metrics' => $metrics,
                'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
            ]),
        ]);

        return array_merge($metrics, [
            'job_id' => $job->id,
            'status' => $job->status,
            'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
        ]);
    }

    /**
     * Run high-volume, streaming Awin bulk ingestion using GZIP Create-a-Feed.
     *
     * @param array{
     *   market: string,
     *   advertiser_id?: ?int,
     *   max_products?: int,
     *   keywords?: ?string,
     *   dry_run?: bool
     * } $options
     * @param ?callable $progressCallback
     * @return array
     */
    public function ingestAwinBulk(array $options, ?callable $progressCallback = null): array
    {
        $startTime = microtime(true);
        $provider = AffiliateProvider::where('code', 'awin')->firstOrFail();
        $marketCode = strtolower($options['market'] ?? 'de');
        $market = Market::where('code', $marketCode)->firstOrFail();

        if (!$this->awinProvider->isConnected($provider)) {
            throw new RuntimeException('Awin is not configured or missing Publisher API credentials.');
        }

        $maxProducts = min((int) ($options['max_products'] ?? 100), 500);
        $advertiserId = !empty($options['advertiser_id']) ? (int) $options['advertiser_id'] : 25962; // Default BlazeVideo DE
        $keywords = $options['keywords'] ?? null;
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $feedSource = $this->awinDatafeedService->resolveFeedSource($provider, $advertiserId, $market);
        $feedUrl = $feedSource?->getUrl();

        if (empty($feedUrl)) {
            throw new RuntimeException("Awin datafeed URL could not be resolved for advertiser [{$advertiserId}].");
        }

        $job = AutomationJob::create([
            'provider_id' => $provider->id,
            'market_id' => $market->id,
            'batch_type' => $dryRun ? 'awin_bulk_ingestion_dry_run' : 'awin_bulk_ingestion',
            'status' => 'processing',
            'total_items' => $maxProducts,
            'processed_items' => 0,
            'failed_items' => 0,
            'started_at' => now(),
            'metadata' => [
                'provider' => 'awin',
                'advertiser_id' => $advertiserId,
                'market' => $marketCode,
                'max_products' => $maxProducts,
            ],
        ]);

        $metrics = [
            'discovered' => 0,
            'eligible' => 0,
            'imported' => 0,
            'created' => 0,
            'updated' => 0,
            'matched' => 0,
            'skipped' => 0,
            'failed' => 0,
            'duplicates' => 0,
            'errors' => [],
        ];

        $streamResult = $this->awinDatafeedService->streamFeedRecords(
            $feedUrl,
            $keywords,
            $market,
            $maxProducts,
            function (array $progress) use ($job, $progressCallback) {
                if ($progressCallback) {
                    $progressCallback(array_merge($progress, ['job_id' => $job->id]));
                }
            }
        );

        if (!$streamResult['success']) {
            $job->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_log' => $streamResult['error'] ?? 'Stream download failed',
            ]);
            throw new RuntimeException("Awin streaming download failed: " . ($streamResult['error'] ?? 'unknown error'));
        }

        $records = $streamResult['records'] ?? [];
        $metrics['discovered'] = $streamResult['rows_examined'] ?? count($records);
        $metrics['skipped'] = $streamResult['rows_skipped'] ?? 0;

        foreach ($records as $record) {
            $metrics['eligible']++;
            $dto = $this->awinProvider->normalizeAwinItem($record, $market);

            if (!$dto) {
                $metrics['skipped']++;
                continue;
            }

            if ($dryRun) {
                $metrics['imported']++;
                $metrics['matched']++;
                continue;
            }

            try {
                $res = $this->ingestionService->ingest($dto, $market);
                if ($res['success']) {
                    $metrics['imported']++;
                    if ($res['action'] === 'created_product') {
                        $metrics['created']++;
                    } else {
                        $metrics['matched']++;
                        $metrics['updated']++;
                    }
                } else {
                    $metrics['failed']++;
                    if (!empty($res['error'])) {
                        $metrics['errors'][] = SecretRedactor::sanitize($res['error']);
                    }
                }
            } catch (Throwable $e) {
                $metrics['failed']++;
                $metrics['errors'][] = SecretRedactor::sanitize($e->getMessage());
            }
        }

        $job->update([
            'status' => $metrics['failed'] > 0 && $metrics['imported'] === 0 ? 'failed' : 'completed',
            'processed_items' => $metrics['imported'],
            'failed_items' => $metrics['failed'],
            'finished_at' => now(),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'error_log' => !empty($metrics['errors']) ? implode("\n", array_slice($metrics['errors'], 0, 30)) : null,
            'metadata' => array_merge($job->metadata ?? [], [
                'final_metrics' => $metrics,
                'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
            ]),
        ]);

        return array_merge($metrics, [
            'job_id' => $job->id,
            'status' => $job->status,
            'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
        ]);
    }

    /**
     * Retry helper for CJ API requests.
     */
    protected function fetchCjWithRetry(
        string $companyId,
        string $apiToken,
        array $partnerIds,
        Market $market,
        int $limit,
        int $offset,
        int $maxRetries = 3
    ): array {
        $attempt = 0;
        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $result = $this->cjProvider->fetchApprovedPartnerProducts(
                    $companyId,
                    $apiToken,
                    $partnerIds,
                    $market,
                    $limit,
                    $offset
                );

                return $result;
            } catch (Throwable $e) {
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                // Exponential backoff: 500ms, 1000ms
                usleep((int) (pow(2, $attempt) * 250000));
            }
        }

        return ['items' => [], 'total' => 0, 'has_more' => false];
    }

    /**
     * Get live catalog and ingestion operational metrics.
     */
    public function getCatalogMetrics(): array
    {
        $cjProvider = AffiliateProvider::where('code', 'cj')->first();
        $awinProvider = AffiliateProvider::where('code', 'awin')->first();
        $amazonProvider = AffiliateProvider::where('code', 'amazon')->first();

        $cjOffersCount = 0;
        if ($cjProvider) {
            $cjOffersCount = Offer::whereHas('retailer', fn($q) => $q->where('affiliate_provider_id', $cjProvider->id))->count();
        }

        $awinOffersCount = 0;
        if ($awinProvider) {
            $awinOffersCount = Offer::whereHas('retailer', fn($q) => $q->where('affiliate_provider_id', $awinProvider->id))->count();
        }

        $amazonOffersCount = 0;
        if ($amazonProvider) {
            $amazonOffersCount = Offer::whereHas('retailer', fn($q) => $q->where('affiliate_provider_id', $amazonProvider->id))->count();
        }

        $recentJobs = AutomationJob::with(['provider', 'market'])
            ->whereIn('batch_type', ['cj_bulk_ingestion', 'awin_bulk_ingestion', 'provider_ingestion', 'cj_bulk_ingestion_dry_run', 'awin_bulk_ingestion_dry_run'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $approvedProgrammes = AffiliateProgramme::where('status', 'approved')
            ->with(['provider'])
            ->get();

        return [
            'total_products' => Product::count(),
            'published_products' => Product::where('status', 'published')->count(),
            'products_added_today' => Product::whereDate('created_at', today())->count(),
            'total_offers' => Offer::count(),
            'active_offers' => Offer::where('is_active', true)->count(),
            'offers_added_today' => Offer::whereDate('created_at', today())->count(),
            'total_retailers' => Retailer::count(),
            'total_markets' => Market::where('is_active', true)->count(),
            'products_with_images' => Product::whereNotNull('primary_image_id')->count(),
            'products_without_gtin' => Product::whereNull('canonical_ean')->whereNull('canonical_upc')->count(),
            'provider_offers' => [
                'cj' => $cjOffersCount,
                'awin' => $awinOffersCount,
                'amazon' => $amazonOffersCount,
            ],
            'approved_programmes' => $approvedProgrammes,
            'recent_jobs' => $recentJobs,
        ];
    }
}
