<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Services\Affiliate\AwinProvider;
use Illuminate\Console\Command;

class AwinProgrammesCommand extends Command
{
    protected $signature = 'affiliate:awin-programmes';
    protected $description = 'List joined advertiser programmes from Awin Publisher API';

    public function handle(AwinProvider $connector): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — AWIN JOINED PROGRAMMES");
        $this->info("==================================================\n");

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$connector->isConnected($provider)) {
            $this->error("Awin provider is not configured or missing credentials.");
            return Command::FAILURE;
        }

        try {
            $programmes = $connector->getJoinedProgrammes($provider);
            $this->info("Found " . count($programmes) . " joined programme(s):\n");

            $rows = [];
            foreach ($programmes as $p) {
                $rows[] = [
                    $p['id'],
                    $p['name'],
                    $p['primaryRegion']['countryCode'] ?? 'DE',
                    $p['currencyCode'] ?? 'EUR',
                    $p['primarySector'] ?? 'General',
                    $p['status'] ?? 'Active',
                    $p['linkStatus'] ?? 'online',
                    $p['displayUrl'] ?? '',
                ];
            }

            $this->table(
                ['Advertiser ID', 'Name', 'Region', 'Currency', 'Sector', 'Status', 'Link Status', 'Website'],
                $rows
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to fetch joined programmes: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
