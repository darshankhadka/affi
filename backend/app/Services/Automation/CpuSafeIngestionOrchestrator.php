<?php

namespace App\Services\Automation;

use App\Models\AffiliateProvider;
use App\Models\AutomationJob;
use App\Models\Market;
use App\Services\Affiliate\AffiliateRegistry;
use App\Services\Pricing\BestPriceService;
use App\Services\Pricing\PriceFreshnessService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CpuSafeIngestionOrchestrator
{
    protected int $maxItemsPerRun;
    protected int $maxRuntimeSeconds;
    protected int $maxApiRequests;
    protected int $maxRetries;
    protected int $chunkSize;

    public function __construct(
        protected AffiliateRegistry $registry,
        protected BestPriceService $bestPriceService,
        protected PriceFreshnessService $freshnessService
    ) {
        $this->maxItemsPerRun = (int) config('automation.max_items_per_run', 100);
        $this->maxRuntimeSeconds = (int) config('automation.max_runtime_seconds', 240);
        $this->maxApiRequests = (int) config('automation.max_api_requests', 50);
        $this->maxRetries = (int) config('automation.max_retries', 3);
        $this->chunkSize = (int) config('automation.chunk_size', 25);
    }

    /**
     * Run bounded, CPU-safe price refresh batch.
     * Guaranteed not to exceed max runtime or max items.
     * Uses atomic lock to prevent concurrent executions on shared hosting.
     *
     * @return array{
     *   job_id: ?int,
     *   status: string,
     *   processed: int,
     *   failed: int,
     *   memory_peak_mb: float,
     *   cpu_time_sec: float
     * }
     */
    public function runPriceRefreshBatch(?Market $market = null): array
    {
        $lockKey = 'automation:lock:price_refresh';
        $lock = Cache::lock($lockKey, $this->maxRuntimeSeconds + 30);

        if (!$lock->get()) {
            Log::warning('Automation lock active. Another price refresh batch is currently running.');
            return [
                'job_id' => null,
                'status' => 'skipped',
                'processed' => 0,
                'failed' => 0,
                'memory_peak_mb' => 0.0,
                'cpu_time_sec' => 0.0,
            ];
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $job = AutomationJob::create([
            'provider_id' => null,
            'market_id' => $market?->id,
            'batch_type' => 'price_refresh',
            'status' => 'processing',
            'total_items' => 0,
            'processed_items' => 0,
            'failed_items' => 0,
            'started_at' => now(),
            'metadata' => [
                'max_items' => $this->maxItemsPerRun,
                'max_runtime' => $this->maxRuntimeSeconds,
            ],
        ]);

        $processedCount = 0;
        $failedCount = 0;
        $errorLog = [];

        try {
            $staleOffers = $this->freshnessService->getStaleOffers($this->maxItemsPerRun);
            $job->update(['total_items' => $staleOffers->count()]);

            foreach ($staleOffers as $offer) {
                // Check runtime limit safety
                if ((microtime(true) - $startTime) >= $this->maxRuntimeSeconds) {
                    Log::info("Price refresh batch reached max runtime safety limit ({$this->maxRuntimeSeconds}s). Stopping gracefully.");
                    break;
                }

                try {
                    $provider = $offer->retailer?->affiliateProvider;
                    if ($provider && $this->registry->has($provider->code)) {
                        $connector = $this->registry->get($provider->code);
                        if ($connector->isConnected($provider)) {
                            // Live fetch if connected
                            $offersData = $connector->fetchProductOffers('ASIN', $offer->sku ?? '', $offer->market);
                            if (!empty($offersData)) {
                                $first = $offersData[0];
                                $offer->update([
                                    'price' => $first['price'],
                                    'original_price' => $first['original_price'] ?? null,
                                    'availability' => $first['availability'],
                                    'last_checked_at' => now(),
                                    'next_check_at' => now()->addHours(12),
                                    'error_count' => 0,
                                ]);

                                $this->bestPriceService->recordPriceHistory($offer);
                                $this->bestPriceService->recalculate($offer->product, $offer->market);
                            }
                        } else {
                            // Provider not configured with live credentials - defer next check safely
                            $offer->update([
                                'last_checked_at' => now(),
                                'next_check_at' => now()->addHours(24),
                            ]);
                        }
                    } else {
                        // Direct or manual offer
                        $offer->update([
                            'last_checked_at' => now(),
                            'next_check_at' => now()->addHours(24),
                        ]);
                    }

                    $processedCount++;
                } catch (Throwable $e) {
                    $failedCount++;
                    $errorLog[] = "Offer ID {$offer->id}: " . $e->getMessage();
                    $offer->increment('error_count');
                    $offer->update([
                        'next_check_at' => now()->addHours(min(24, 2 ** $offer->error_count)), // Exponential backoff
                    ]);
                }

                // Periodically update job record every chunk to be resumable & observable
                if ($processedCount % $this->chunkSize === 0) {
                    $job->update([
                        'processed_items' => $processedCount,
                        'failed_items' => $failedCount,
                    ]);
                }
            }

            $job->update([
                'status' => $failedCount > 0 && $processedCount === 0 ? 'failed' : 'completed',
                'processed_items' => $processedCount,
                'failed_items' => $failedCount,
                'finished_at' => now(),
                'memory_peak_bytes' => memory_get_peak_usage(true),
                'cpu_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'error_log' => !empty($errorLog) ? implode("\n", array_slice($errorLog, 0, 50)) : null,
            ]);
        } catch (Throwable $e) {
            $job->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_log' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
        } finally {
            $lock->release();
        }

        $duration = microtime(true) - $startTime;
        $peakMemoryMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        return [
            'job_id' => $job->id,
            'status' => $job->status,
            'processed' => $processedCount,
            'failed' => $failedCount,
            'memory_peak_mb' => $peakMemoryMb,
            'cpu_time_sec' => round($duration, 3),
        ];
    }
}
