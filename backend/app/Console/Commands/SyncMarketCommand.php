<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\Retailer;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Console\Command;

class SyncMarketCommand extends Command
{
    protected $signature = 'automation:sync-market {market : Market ISO code (e.g. DE, GB, US)} {--limit=10 : Max items per retailer} {--dry-run : Simulate ingestion}';
    protected $description = 'Perform a bounded, idempotent synchronization for all configured retailers in a market';

    public function handle(AffiliateRegistry $registry): int
    {
        $marketCode = strtolower($this->argument('market'));
        $market = Market::where('code', $marketCode)->first();

        if (!$market) {
            $this->error("Market [{$marketCode}] not found in database.");
            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("================================================================");
        $this->info("ARIKARTECH — MARKET SYNCHRONIZATION: " . strtoupper($marketCode));
        $this->info("================================================================");
        $this->info("Market: {$market->name} | Currency: {$market->defaultCurrency?->code} | Dry Run: " . ($dryRun ? 'YES' : 'NO'));

        $retailers = Retailer::where('market_code', $marketCode)
            ->where('is_active', true)
            ->with('affiliateProvider')
            ->get();

        $this->info("Found {$retailers->count()} priority retailers for market [{$marketCode}].");

        foreach ($retailers as $retailer) {
            $provider = $retailer->affiliateProvider;
            $this->line("• Checking {$retailer->name} ({$retailer->slug}) [Provider: " . ($provider ? $provider->code : 'none') . "]...");

            if (!$provider || !$registry->has($provider->code)) {
                $this->warn("  → Skipping: Provider not registered or unassigned.");
                continue;
            }

            $instance = $registry->get($provider->code);
            if (!$instance->isConnected($provider)) {
                $this->line("  → Status: Not configured with live credentials. Skipping gracefully.");
                continue;
            }

            $this->info("  → Syncing active provider [{$provider->code}] (limit: {$limit})...");
            $this->call('affiliate:sync', [
                'retailer' => $retailer->slug,
                '--limit' => $limit,
                '--dry-run' => $dryRun,
            ]);
        }

        $this->info("✔ Market sync completed for [{$marketCode}].");
        return Command::SUCCESS;
    }
}
