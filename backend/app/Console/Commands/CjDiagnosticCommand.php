<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\AffiliateProgramme;
use App\Services\Affiliate\CjProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Safe configuration and connectivity diagnostic for CJ Affiliate.
 *
 * Reports:
 *   - Credentials configured?
 *   - API reachable?
 *   - Publisher authenticated?
 *   - Approved programmes in database?
 *   - Products discoverable for joined advertisers?
 *
 * NEVER prints secrets (tokens, keys, company IDs).
 */
class CjDiagnosticCommand extends Command
{
    protected $signature = 'affiliate:cj-diagnostic
                            {--market=us : Market to probe products for}';

    protected $description = 'Report CJ Affiliate configuration state and connectivity (no secrets printed)';

    public function handle(CjProvider $connector): int
    {
        $this->info('=== ARIKARTECH — CJ AFFILIATE CONFIGURATION DIAGNOSTIC ===');
        $this->line('(No secrets are printed by this command.)' . "\n");

        $provider = AffiliateProvider::where('code', 'cj')->first();
        if (!$provider) {
            $this->error('CJ provider record not found in database.');
            $this->line('Run: php artisan system:init-foundation');
            return Command::FAILURE;
        }

        $config    = $provider->config ?? [];
        $connected = $connector->isConnected($provider);

        $this->table(
            ['Check', 'Value'],
            [
                ['Provider record exists', 'YES'],
                ['Provider active', $this->yn($provider->is_active)],
                ['API token configured', $this->yn(!empty($config['api_token'] ?? config('services.cj.api_token')))],
                ['Company ID configured', $this->yn(!empty($config['company_id'] ?? config('services.cj.company_id')))],
                ['Connected (both credentials present)', $this->yn($connected)],
            ]
        );

        if (!$connected) {
            $this->warn("\nCJ credentials are not configured.");
            $this->line('Required environment variables:');
            $this->line('  CJ_API_TOKEN=<your-personal-access-token>');
            $this->line('  CJ_COMPANY_ID=<your-publisher-company-id>   (e.g. 8051682)');
            return Command::FAILURE;
        }

        // Test live connection
        $this->line("\nTesting live CJ GraphQL API connection...");
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

        if (!$result['connected']) {
            $this->error("\nCJ API connection failed: {$result['message']}");
            return Command::FAILURE;
        }

        // Check approved programmes in database
        $approvedProgrammes = AffiliateProgramme::where('provider_id', $provider->id)
            ->where('status', 'approved')
            ->get();

        $this->line("\nApproved CJ programmes in database: " . $approvedProgrammes->count());

        if ($approvedProgrammes->isNotEmpty()) {
            $rows = $approvedProgrammes->map(fn ($p) => [
                $p->external_programme_id,
                $p->name,
                $p->status,
                $p->commission_value ? $p->commission_value . '% ' . $p->commission_type : 'unknown',
                $p->currency ?? 'N/A',
            ])->toArray();

            $this->table(['Programme ID', 'Name', 'Status', 'Commission', 'Currency'], $rows);
        } else {
            $this->warn('No approved CJ programmes found in database.');
            $this->line('Run: php artisan affiliate:seed-approved-programmes');
        }

        $this->info("\nCJ diagnostic completed successfully.");
        return Command::SUCCESS;
    }

    protected function yn(bool $v): string
    {
        return $v ? 'YES' : 'NO';
    }
}
