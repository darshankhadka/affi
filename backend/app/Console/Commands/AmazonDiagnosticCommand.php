<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Services\Affiliate\AmazonProvider;
use Illuminate\Console\Command;

/**
 * Safe configuration and connectivity diagnostic for Amazon Associates / PA-API 5.0.
 *
 * Reports:
 *   - PA-API credentials configured?
 *   - API reachable?
 *   - Associate tags configured per market?
 *   - Supported markets?
 *
 * NEVER prints secrets (access keys, secret keys, associate tags).
 */
class AmazonDiagnosticCommand extends Command
{
    protected $signature = 'affiliate:amazon-diagnostic';

    protected $description = 'Report Amazon Associates / PA-API 5.0 configuration state (no secrets printed)';

    public function handle(AmazonProvider $connector): int
    {
        $this->info('=== ARIKARTECH — AMAZON ASSOCIATES CONFIGURATION DIAGNOSTIC ===');
        $this->line('(No secrets are printed by this command.)' . "\n");

        $provider = AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider) {
            $this->error('Amazon provider record not found in database.');
            $this->line('Run: php artisan system:init-foundation');
            return Command::FAILURE;
        }

        $config    = $provider->config ?? [];
        $connected = $connector->isConnected($provider);

        // Check credential presence (not values)
        $hasAccessKey = !empty($config['access_key'] ?? config('services.amazon.paapi_key'));
        $hasSecretKey = !empty($config['secret_key'] ?? config('services.amazon.paapi_secret'));

        $this->table(
            ['Check', 'Value'],
            [
                ['Provider record exists', 'YES'],
                ['Provider active', $this->yn($provider->is_active)],
                ['PA-API access key configured', $this->yn($hasAccessKey)],
                ['PA-API secret key configured', $this->yn($hasSecretKey)],
                ['Connected (both credentials present)', $this->yn($connected)],
            ]
        );

        if (!$connected) {
            $this->warn("\nAmazon PA-API credentials are not configured.");
            $this->line('Required environment variables:');
            $this->line('  AMAZON_PAAPI_KEY=<access-key>');
            $this->line('  AMAZON_PAAPI_SECRET=<secret-key>');
            return Command::FAILURE;
        }

        // Check associate tags per market
        $this->line("\nAssociate tag coverage:");
        $supportedMarkets = $connector->getSupportedMarkets();
        $tagRows = [];
        foreach ($supportedMarkets as $market) {
            $hasTag = !empty(config("services.amazon.tags.{$market}"));
            $tagRows[] = [strtoupper($market), $this->yn($hasTag)];
        }
        $this->table(['Market', 'Tag configured'], $tagRows);

        // Test live PA-API connection
        $this->line("\nTesting live Amazon PA-API 5.0 connection...");
        $result = $connector->testConnection($provider);

        $this->table(
            ['Check', 'Value'],
            [
                ['API reachable', $this->yn($result['connected'])],
                ['Status', $result['status']],
                ['Latency (ms)', $result['latency_ms'] ?? 'N/A'],
                ['Message', $result['message']],
            ]
        );

        if ($result['connected']) {
            $this->info("\nAmazon PA-API is operational.");
            $this->line('Supported markets: ' . implode(', ', $supportedMarkets));
            $this->line('Supported currencies: ' . implode(', ', $connector->getSupportedCurrencies()));
        } else {
            $this->warn("\nAmazon PA-API connection issue: {$result['message']}");

            if ($result['status'] === 'rate_limited') {
                $this->line('PA-API is rate-limited. This is normal for accounts with low traffic.');
                $this->line('Amazon requires qualifying sales before granting full PA-API access.');
            }
        }

        $this->info("\nAmazon diagnostic completed.");
        return Command::SUCCESS;
    }

    protected function yn(bool $v): string
    {
        return $v ? 'YES' : 'NO';
    }
}
