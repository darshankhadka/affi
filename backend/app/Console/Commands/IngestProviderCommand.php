<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\AutomationJob;
use App\Models\Market;
use App\Services\Affiliate\AffiliateRegistry;
use App\Services\Ingestion\ProductIngestionService;
use App\Support\SecretRedactor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class IngestProviderCommand extends Command
{
    protected $signature = 'automation:ingest-provider 
        {--provider=amazon : Affiliate provider code (amazon, awin, etc.)}
        {--market=us : Target market code}
        {--limit=25 : Maximum items to process in this run}
        {--keywords= : Search query or category keywords}
        {--dry-run : Perform provider request + normalization + matching but write nothing}';

    protected $description = 'Run a CPU-safe, bounded product ingestion batch from an authorized affiliate provider';

    public function handle(
        AffiliateRegistry $registry,
        ProductIngestionService $ingestionService
    ): int {
        $providerCode = (string) $this->option('provider');
        $marketCode = (string) $this->option('market');
        $limit = min((int) $this->option('limit'), 50);
        $keywords = $this->option('keywords') ?: 'Laptop';
        $dryRun = (bool) $this->option('dry-run');

        $provider = AffiliateProvider::where('code', $providerCode)->first();
        if (!$provider) {
            $this->error("Affiliate provider '{$providerCode}' is not registered.");
            return Command::FAILURE;
        }

        $market = Market::where('code', strtolower($marketCode))->first();
        if (!$market) {
            $this->error("Market '{$marketCode}' does not exist.");
            return Command::FAILURE;
        }

        if (!$registry->has($providerCode)) {
            $this->error("Provider driver for '{$providerCode}' is not loaded.");
            return Command::FAILURE;
        }

        $connector = $registry->get($providerCode);
        if (!$connector->isConnected($provider)) {
            $this->warn("Provider '{$provider->name}' is currently disconnected or unconfigured. Live sync skipped.");
            return Command::SUCCESS;
        }

        // Mutual exclusion lock with deterministic TTL + owner token so a late expiry
        // cannot let a second process release our lock. TTL is bounded to avoid the
        // "24 hour lock" trap and calibrated to the configured max runtime.
        $runtimeCap = (int) config('automation.max_runtime_seconds', 240);
        $lockTtl = max(300, min(900, $runtimeCap + 120));
        $lockKey = "automation:lock:ingest:{$providerCode}:{$marketCode}";
        $lock = Cache::lock($lockKey, $lockTtl);

        if (!$lock->get()) {
            $this->warn("Another ingestion job for '{$providerCode}' ({$marketCode}) is currently running. Exiting cleanly.");
            return Command::SUCCESS;
        }

        $this->info("Starting bounded ingestion for '{$provider->name}' ({$marketCode}) — Limit: {$limit} items" . ($dryRun ? ' [DRY RUN]' : ''));

        $job = AutomationJob::create([
            'provider_id' => $provider->id,
            'market_id' => $market->id,
            'batch_type' => $dryRun ? 'provider_ingestion_dry_run' : 'provider_ingestion',
            'status' => 'processing',
            'total_items' => 0,
            'processed_items' => 0,
            'failed_items' => 0,
            'started_at' => now(),
            'metadata' => [
                'provider' => $providerCode,
                'market' => $marketCode,
                'keywords' => $keywords,
                'limit' => $limit,
                'dry_run' => $dryRun,
                'lock_ttl_seconds' => $lockTtl,
            ],
        ]);

        $created = 0;
        $matched = 0;
        $failed = 0;
        $errorLog = [];

        try {
            $normalizedProducts = $connector->searchProducts($keywords, $market, null, $limit);
            $job->update(['total_items' => count($normalizedProducts)]);

            foreach ($normalizedProducts as $dto) {
                if ($dryRun) {
                    // Validate + match (read-only) but write nothing.
                    $matchedInfo = $this->dryRunEvaluate($ingestionService, $dto, $market);
                    if ($matchedInfo === 'created') {
                        $created++;
                    } else {
                        $matched++;
                    }
                    continue;
                }

                try {
                    $res = $ingestionService->ingest($dto, $market);
                    if ($res['success']) {
                        if ($res['action'] === 'created_product') {
                            $created++;
                        } else {
                            $matched++;
                        }
                    } else {
                        $failed++;
                        if (!empty($res['error'])) {
                            $errorLog[] = SecretRedactor::sanitize($res['error']);
                        }
                    }
                } catch (Throwable $e) {
                    $failed++;
                    $errorLog[] = SecretRedactor::sanitize($e->getMessage());
                }
            }

            // Accurate status: never mark "completed" when the provider yielded nothing
            // because it failed. Distinguish zero-result, partial, and full success.
            $processed = $created + $matched;
            if ($failed > 0 && $processed === 0) {
                $status = 'failed';
                $resultNote = 'failed';
            } elseif ($processed === 0) {
                $status = 'completed_zero_results';
                $resultNote = 'zero_results';
            } elseif ($failed > 0) {
                $status = 'partial';
                $resultNote = 'partial';
            } else {
                $status = 'completed';
                $resultNote = 'ok';
            }

            $job->update([
                'status' => $status,
                'processed_items' => $processed,
                'failed_items' => $failed,
                'finished_at' => now(),
                'memory_peak_bytes' => memory_get_peak_usage(true),
                'cpu_time_ms' => null,
                'error_log' => !empty($errorLog) ? implode("\n", array_slice($errorLog, 0, 50)) : null,
                'metadata' => array_merge($job->metadata ?? [], [
                    'result' => $resultNote,
                    'created' => $created,
                    'matched' => $matched,
                ]),
            ]);

            $elapsed = round(microtime(true) - ($job->started_at?->timestamp ?? microtime(true)), 2);
            $this->info("Ingestion finished in {$elapsed}s. Created: {$created}, Matched: {$matched}, Failed: {$failed}. Status: {$status}");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $job->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_log' => SecretRedactor::sanitize($e->getMessage()) . "\n" . SecretRedactor::sanitize($e->getTraceAsString()),
            ]);
            $this->error("Ingestion failed: " . SecretRedactor::sanitize($e->getMessage()));

            return Command::FAILURE;
        } finally {
            // Only releases if we still own it (owner token safety).
            $lock->release();
        }
    }

    /**
     * Dry-run evaluation: run normalization + matching + full ingest inside an
     * explicit outer transaction that is rolled back, so nothing is persisted.
     */
    protected function dryRunEvaluate(ProductIngestionService $ingestionService, $dto, Market $market): string
    {
        DB::beginTransaction();
        try {
            $res = $ingestionService->ingest($dto, $market);
            $action = $res['action'] === 'created_product' ? 'created' : 'matched';
        } catch (Throwable $e) {
            $action = 'matched';
        } finally {
            DB::rollBack();
        }

        return $action;
    }
}
