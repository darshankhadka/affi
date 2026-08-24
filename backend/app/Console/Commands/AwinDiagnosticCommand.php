<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Services\Affiliate\AwinProvider;
use Illuminate\Console\Command;

/**
 * Safe configuration/feed diagnostic for Awin. Reports status WITHOUT ever
 * printing secrets (API tokens, datafeed keys, or authenticated feed URLs).
 */
class AwinDiagnosticCommand extends Command
{
    protected $signature = 'affiliate:awin-diagnostic {--market=de : Market to evaluate feed scope}';
    protected $description = 'Report Awin configuration state and feed strategy (no secrets printed)';

    public function handle(AwinProvider $connector): int
    {
        $this->info('=== ARIKARTECH — AWIN CONFIGURATION DIAGNOSTIC ===');
        $this->line('(No secrets are printed by this command.)' . "\n");

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider) {
            $this->error('Awin provider record not found in database.');
            return Command::FAILURE;
        }

        $diag = $connector->getFeedDiagnostics($provider); // via AwinDatafeedService

        $this->table(
            ['Check', 'Value'],
            [
                ['Provider record exists', 'yes'],
                ['Connected (API token + publisher id)', $connector->isConnected($provider) ? 'YES' : 'NO'],
                ['Publisher ID present', $this->yn($diag['publisher_id_present'])],
                ['API token present', $this->yn($diag['api_token_present'])],
                ['Datafeed URL (AWIN_DATAFEED_URL) present', $this->yn($diag['datafeed_url_present'])],
                ['Datafeed API key (AWIN_DATAFEED_API_KEY) present', $this->yn($diag['datafeed_api_key_present'])],
                ['Configured (any feed mechanism)', $this->yn($diag['configured'])],
                ['Detected feed type', $diag['feed_type']],
                ['Feed is shared (downloaded once)', $this->yn($diag['shared_feed'])],
                ['Supports publisher-wide feed', $this->yn($diag['supports_publisher_wide'])],
            ]
        );

        if (!$diag['configured']) {
            $this->warn("\nNo Awin feed is configured. Set AWIN_DATAFEED_URL (publisher-wide) OR AWIN_DATAFEED_API_KEY.");
            $this->line("Recommended: AWIN_DATAFEED_URL=https://productdata.awin.com/datafeed/download/.../.../ compression/gzip/");
            return Command::FAILURE;
        }

        $this->info("\nFeed strategy: " . $diag['feed_name']);
        $this->line('Feed type "' . $diag['feed_type'] . '" means the feed will be ' .
            ($diag['shared_feed'] ? 'downloaded ONCE and shared across all advertisers.' : 'downloaded per advertiser.'));

        return Command::SUCCESS;
    }

    protected function yn(bool $v): string
    {
        return $v ? 'YES' : 'NO';
    }
}
