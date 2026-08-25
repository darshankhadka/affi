<?php

namespace App\Console\Commands;

use App\Models\AffiliateProgramme;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Services\Affiliate\AwinDatafeedService;
use App\Services\Affiliate\AwinProvider;
use App\Services\Affiliate\Feeds\FeedSourceFactory;
use App\Services\Ingestion\ProductIngestionService;
use App\Support\SecretRedactor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AwinIngestCommand extends Command
{
    protected $signature = 'affiliate:awin-ingest
        {--programme= : Specific Awin programme/advertiser ID (e.g. 25962, 57897, 8800)}
        {--market= : Target market code (de, dk, us, be, gb, etc.)}
        {--limit=100 : Total records to import per programme}
        {--batch=50 : Database transaction batch chunk size}
        {--dry-run : Simulate parsing, normalization, and matching without modifying database}
        {--force : Bypass mutex run locks}
        {--all-approved : Ingest all approved Awin programmes sequentially}';

    protected $description = 'Ingest real merchant product feeds from approved Awin programmes into canonical catalog and offers';

    public function handle(
        AwinProvider $awinProvider,
        AwinDatafeedService $datafeedService,
        ProductIngestionService $ingestionService
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $allApproved = (bool) $this->option('all-approved');
        $limit = max(1, (int) $this->option('limit'));
        $batchSize = max(1, min((int) $this->option('batch'), 100));
        $programmeInput = $this->option('programme');
        $marketInput = $this->option('market');

        $this->info("==================================================");
        $this->info("ARIKARTECH — AWIN REAL CATALOG INGESTION ENGINE");
        $this->info("==================================================");
        if ($dryRun) {
            $this->warn("[DRY RUN MODE] Simulating feed ingestion — zero database writes.\n");
        }

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$awinProvider->isConnected($provider)) {
            $this->error("Awin provider is disconnected or unconfigured.");
            return Command::FAILURE;
        }

        // Determine list of programmes to process
        /** @var \Illuminate\Database\Eloquent\Collection<int, AffiliateProgramme> $programmes */
        if ($allApproved) {
            $programmes = AffiliateProgramme::where('provider_id', $provider->id)
                ->where('status', 'approved')
                ->get();
        } elseif ($programmeInput) {
            $programmes = AffiliateProgramme::where('provider_id', $provider->id)
                ->where('external_programme_id', (string) $programmeInput)
                ->get();

            if ($programmes->isEmpty()) {
                // Temporary on-demand programme definition if valid numeric ID
                $p = new AffiliateProgramme([
                    'provider_id' => $provider->id,
                    'external_programme_id' => (string) $programmeInput,
                    'name' => "Awin Programme #{$programmeInput}",
                    'status' => 'approved',
                ]);
                $programmes = collect([$p]);
            }
        } else {
            $this->error("Please specify either --programme={id} or --all-approved.");
            return Command::FAILURE;
        }

        $grandTotals = [
            'programmes' => 0,
            'records_read' => 0,
            'products_created' => 0,
            'products_updated' => 0,
            'offers_created' => 0,
            'offers_updated' => 0,
            'skipped_invalid' => 0,
            'duplicate_matches' => 0,
            'images_available' => 0,
        ];

        foreach ($programmes as $programme) {
            $grandTotals['programmes']++;
            $advId = (int) $programme->external_programme_id;
            $meta = $programme->network_metadata ?? [];

            // Resolve target market
            $mCode = strtolower($marketInput ?: ($meta['market'] ?? 'de'));
            $market = Market::where('code', $mCode)->first() ?? Market::where('code', 'de')->firstOrFail();

            $lockKey = "awin_ingest_lock_{$advId}";
            $lock = Cache::lock($lockKey, 300);

            if (!$force && !$lock->get()) {
                $this->warn("Skipping Programme {$programme->name} (#{$advId}): another process is currently running.");
                continue;
            }

            try {
                $this->info("--------------------------------------------------");
                $this->info("Programme: <comment>{$programme->name}</comment> (ID: {$advId})");
                $this->line("Target Market: <comment>{$market->name} ({$market->code})</comment> | Limit: {$limit}");

                $feedSource = $datafeedService->resolveFeedSource($provider, $advId, $market);
                if (!$feedSource) {
                    $this->error("Feed source could not be resolved for advertiser #{$advId}.");
                    continue;
                }

                $t0 = microtime(true);
                $this->line("Feed: <info>connected</info> ({$feedSource->getType()})");

                $streamResult = $datafeedService->streamFeedRecords(
                    $feedSource,
                    null,
                    $market,
                    $limit,
                    function (array $progress) {
                        $kb = round($progress['bytes_received'] / 1024, 1);
                        $this->line("  [Stream] Received: {$kb} KB | Rows Parsed: {$progress['rows_parsed']} | Elapsed: {$progress['elapsed_ms']}ms");
                    }
                );

                if (!$streamResult['success']) {
                    $this->error("Streaming failed: " . ($streamResult['error'] ?? 'Unknown error'));
                    continue;
                }

                $records = $streamResult['records'] ?? [];
                $recordsRead = $streamResult['rows_examined'] ?? count($records);
                $productsCreated = 0;
                $productsUpdated = 0;
                $offersCreated = 0;
                $offersUpdated = 0;
                $skippedInvalid = $streamResult['rows_skipped'] ?? 0;
                $duplicateMatches = 0;
                $imagesAvailable = 0;

                // Process in chunks of batchSize
                $chunks = array_chunk($records, $batchSize);
                foreach ($chunks as $chunk) {
                    foreach ($chunk as $row) {
                        if (empty($row['merchant_id'])) {
                            $row['merchant_id'] = (string) $advId;
                        }
                        if (empty($row['merchant_name'])) {
                            $row['merchant_name'] = $programme->name;
                        }

                        if (!empty($row['aw_image_url']) || !empty($row['merchant_image_url']) || !empty($row['large_image'])) {
                            $imagesAvailable++;
                        }

                        $dto = $awinProvider->normalizeAwinItem($row, $market);
                        if (!$dto) {
                            $skippedInvalid++;
                            continue;
                        }

                        if ($dryRun) {
                            $productsCreated++;
                            $offersCreated++;
                            continue;
                        }

                        try {
                            $res = $ingestionService->ingest($dto, $market);
                            if ($res['success']) {
                                if ($res['action'] === 'created_product') {
                                    $productsCreated++;
                                } else {
                                    $productsUpdated++;
                                    $duplicateMatches++;
                                }

                                if ($res['offer']) {
                                    if ($res['offer']->wasRecentlyCreated) {
                                        $offersCreated++;
                                    } else {
                                        $offersUpdated++;
                                    }
                                }
                            } else {
                                $skippedInvalid++;
                            }
                        } catch (Throwable $e) {
                            $skippedInvalid++;
                        }
                    }
                }

                $duration = round(microtime(true) - $t0, 2);

                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Records read', $recordsRead],
                        ['Products created', $productsCreated],
                        ['Products updated', $productsUpdated],
                        ['Offers created', $offersCreated],
                        ['Offers updated', $offersUpdated],
                        ['Skipped invalid', $skippedInvalid],
                        ['Duplicate canonical matches', $duplicateMatches],
                        ['Images available', $imagesAvailable],
                        ['Duration', "{$duration}s"],
                    ]
                );

                $grandTotals['records_read'] += $recordsRead;
                $grandTotals['products_created'] += $productsCreated;
                $grandTotals['products_updated'] += $productsUpdated;
                $grandTotals['offers_created'] += $offersCreated;
                $grandTotals['offers_updated'] += $offersUpdated;
                $grandTotals['skipped_invalid'] += $skippedInvalid;
                $grandTotals['duplicate_matches'] += $duplicateMatches;
                $grandTotals['images_available'] += $imagesAvailable;

            } finally {
                $lock->release();
            }
        }

        if ($allApproved && $grandTotals['programmes'] > 1) {
            $this->info("\n==================================================");
            $this->info("GRAND TOTALS ACROSS ALL APPROVED PROGRAMMES");
            $this->info("==================================================");
            $this->table(
                ['Metric', 'Total'],
                [
                    ['Programmes Processed', $grandTotals['programmes']],
                    ['Total Records Read', $grandTotals['records_read']],
                    ['Total Products Created', $grandTotals['products_created']],
                    ['Total Products Updated', $grandTotals['products_updated']],
                    ['Total Offers Created', $grandTotals['offers_created']],
                    ['Total Offers Updated', $grandTotals['offers_updated']],
                    ['Total Skipped/Invalid', $grandTotals['skipped_invalid']],
                    ['Total Canonical Matches', $grandTotals['duplicate_matches']],
                    ['Total Images Imported', $grandTotals['images_available']],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
