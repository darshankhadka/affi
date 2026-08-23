<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\AutomationJob;
use App\Models\Market;
use App\Services\Affiliate\AffiliateRegistry;
use App\Services\Affiliate\AmazonProvider;
use App\Services\Ingestion\ProductIngestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class IngestProviderCommand extends Command
{
    protected $signature = 'automation:ingest-provider 
        {--provider=amazon : Affiliate provider code (amazon, awin, etc.)}
        {--market=us : Target market code}
        {--limit=25 : Maximum items to process in this run}
        {--keywords= : Search query or category keywords}';

    protected $description = 'Run a CPU-safe, bounded product ingestion batch from an authorized affiliate provider';

    public function handle(
        AffiliateRegistry $registry,
        ProductIngestionService $ingestionService
    ): int {
        $providerCode = (string) $this->option('provider');
        $marketCode = (string) $this->option('market');
        $limit = min((int) $this->option('limit'), 50);
        $keywords = $this->option('keywords') ?: 'Laptop';

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

        // Mutual exclusion lock
        $lockKey = "automation:lock:ingest:{$providerCode}:{$marketCode}";
        $lock = Cache::lock($lockKey, 240);

        if (!$lock->get()) {
            $this->warn("Another ingestion job for '{$providerCode}' ({$marketCode}) is currently running. Exiting cleanly.");
            return Command::SUCCESS;
        }

        $startTime = microtime(true);
        $this->info("Starting bounded ingestion for '{$provider->name}' ({$marketCode}) — Limit: {$limit} items...");

        $job = AutomationJob::create([
            'provider_id' => $provider->id,
            'market_id' => $market->id,
            'batch_type' => 'provider_ingestion',
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
            ],
        ]);

        $created = 0;
        $matched = 0;
        $failed = 0;
        $errorLog = [];

        try {
            if ($connector instanceof AmazonProvider) {
                $normalizedProducts = $connector->searchItems($keywords, $market, null, $limit);
                $job->update(['total_items' => count($normalizedProducts)]);

                foreach ($normalizedProducts as $dto) {
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
                                $errorLog[] = $res['error'];
                            }
                        }
                    } catch (Throwable $e) {
                        $failed++;
                        $errorLog[] = "Ingestion error for '{$dto->name}': " . $e->getMessage();
                    }
                }
            }

            $job->update([
                'status' => 'completed',
                'processed_items' => $created + $matched,
                'failed_items' => $failed,
                'finished_at' => now(),
                'memory_peak_bytes' => memory_get_peak_usage(true),
                'cpu_time_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'error_log' => !empty($errorLog) ? implode("\n", array_slice($errorLog, 0, 50)) : null,
            ]);

            $elapsed = round(microtime(true) - $startTime, 2);
            $this->info("Ingestion completed in {$elapsed}s. Created: {$created}, Matched: {$matched}, Failed: {$failed}.");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $job->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_log' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);
            $this->error("Ingestion failed: " . $e->getMessage());

            return Command::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
