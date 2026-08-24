<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\Retailer;
use App\Services\Affiliate\AffiliateRegistry;
use App\Services\Ingestion\ProductIngestionService;
use Illuminate\Console\Command;

class AffiliateSyncCommand extends Command
{
    protected $signature = 'affiliate:sync {retailer : Retailer slug or code} {--limit=10 : Max items to ingest} {--dry-run : Simulate ingestion without database write}';
    protected $description = 'Perform a bounded, idempotent synchronization for a specific retailer';

    public function handle(AffiliateRegistry $registry, ProductIngestionService $ingestionService): int
    {
        $identifier = $this->argument('retailer');
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $retailer = Retailer::where('slug', $identifier)
            ->orWhere('code', $identifier)
            ->with(['affiliateProvider', 'market'])
            ->first();

        if (!$retailer) {
            $this->error("Retailer [{$identifier}] not found in database.");
            return Command::FAILURE;
        }

        $provider = $retailer->affiliateProvider;
        if (!$provider) {
            $this->warn("Retailer [{$retailer->name}] has no assigned affiliate provider.");
            return Command::SUCCESS;
        }

        $this->info("Starting sync for retailer: {$retailer->name} ({$retailer->slug}) via {$provider->name}");
        $this->info("Limit: {$limit} | Dry Run: " . ($dryRun ? 'YES' : 'NO'));

        if (!$registry->has($provider->code)) {
            $this->error("Provider [{$provider->code}] is not registered.");
            return Command::FAILURE;
        }

        $providerInstance = $registry->get($provider->code);
        if (!$providerInstance->isConnected($provider)) {
            $this->warn("Provider [{$provider->code}] is not configured or connected. Sync skipped safely.");
            return Command::SUCCESS;
        }

        $market = $retailer->market ?? Market::where('code', $retailer->market_code)->first() ?? Market::first();
        $batch = $providerInstance->syncCatalogBatch($market, $limit);

        $this->info("Sync completed: {$batch['processed']} items processed. Has More: " . ($batch['has_more'] ? 'YES' : 'NO'));
        
        $retailer->update([
            'last_successful_sync_at' => now(),
            'last_error' => null,
        ]);

        return Command::SUCCESS;
    }
}
