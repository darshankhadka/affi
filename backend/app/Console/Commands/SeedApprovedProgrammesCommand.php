<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\AffiliateProgramme;
use Illuminate\Console\Command;

/**
 * Seeds the known approved Awin programme records.
 *
 * This command is idempotent — safe to run multiple times.
 * It uses updateOrCreate to avoid duplicating records.
 *
 * IMPORTANT:
 *   These are real, approved publisher relationships.
 *   Do NOT add programmes here that are not yet approved.
 *   Approval must be confirmed in the Awin Publisher Hub before seeding.
 */
class SeedApprovedProgrammesCommand extends Command
{
    protected $signature = 'affiliate:seed-approved-programmes
                            {--dry-run : Show what would be seeded without writing}';

    protected $description = 'Seed known approved Awin affiliate programme records (idempotent)';

    /**
     * Approved Awin programmes for publisher ID 3053247.
     *
     * Sources: Awin Publisher Hub, confirmed approval status.
     * Do not hardcode these IDs elsewhere in the application.
     *
     * Fields:
     *   external_programme_id : Awin Advertiser/Programme ID
     *   name                  : Programme display name
     *   commission_type       : Type of commission structure
     *   commission_value      : Value (e.g. 5.65 means 5.65%)
     *   currency              : Commission/offer currency
     *   cookie_duration_days  : Attribution window
     *   epc                   : Earnings per click (informational)
     *   conversion_rate       : Conversion rate % (informational)
     */
    protected array $approvedProgrammes = [
        [
            'external_programme_id' => '25962',
            'name'                  => 'BlazeVideo DE',
            'publisher_id'          => '3053247',
            'commission_type'       => 'percentage',
            'commission_value'      => 5.65,
            'currency'              => 'EUR',
            'cookie_duration_days'  => 54,
            'epc'                   => 0.37,
            'conversion_rate'       => 5.65,
            'network_metadata'      => [
                'market' => 'de',
                'domain' => 'blazevideos.de',
                'note'   => 'Confirmed approved in Awin Publisher Hub',
            ],
        ],
        [
            'external_programme_id' => '8800',
            'name'                  => 'mcdaekonline DK',
            'publisher_id'          => '3053247',
            'commission_type'       => 'percentage',
            'commission_value'      => 3.03,
            'currency'              => 'DKK',
            'cookie_duration_days'  => 32,
            'epc'                   => 0.68,
            'conversion_rate'       => 3.03,
            'network_metadata'      => [
                'market' => 'dk',
                'note'   => 'Confirmed approved in Awin Publisher Hub',
            ],
        ],
        [
            'external_programme_id' => '57897',
            'name'                  => 'Geekbuying DE',
            'publisher_id'          => '3053247',
            'commission_type'       => 'percentage',
            'commission_value'      => 3.66,
            'currency'              => 'EUR',
            'cookie_duration_days'  => 58,
            'epc'                   => 0.52,
            'conversion_rate'       => 3.66,
            'network_metadata'      => [
                'market' => 'de',
                'domain' => 'geekbuying.com',
                'note'   => 'Confirmed approved in Awin Publisher Hub',
            ],
        ],
        [
            'external_programme_id' => '75408',
            'name'                  => 'Nothingprojector',
            'publisher_id'          => '3053247',
            'commission_type'       => 'percentage',
            'commission_value'      => 1.25,
            'currency'              => 'USD',
            'cookie_duration_days'  => 74,
            'epc'                   => 0.60,
            'conversion_rate'       => 1.25,
            'network_metadata'      => [
                'note' => 'Confirmed approved in Awin Publisher Hub',
            ],
        ],
        [
            'external_programme_id' => '90211',
            'name'                  => 'Fast Technology Limited',
            'publisher_id'          => '3053247',
            'commission_type'       => 'percentage',
            'commission_value'      => 1.19,
            'currency'              => 'USD',
            'cookie_duration_days'  => 71,
            'epc'                   => 0.09,
            'conversion_rate'       => 1.19,
            'network_metadata'      => [
                'note' => 'Confirmed approved in Awin Publisher Hub',
            ],
        ],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('=== ARIKARTECH — AFFILIATE PROGRAMME SEEDER ===');
        if ($dryRun) {
            $this->warn('[DRY RUN] No records will be written.');
        }

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider) {
            $this->error('Awin affiliate provider record not found. Run system:init-foundation first.');
            return Command::FAILURE;
        }

        $rows    = [];
        $created = 0;
        $updated = 0;

        foreach ($this->approvedProgrammes as $prog) {
            if ($dryRun) {
                $rows[] = [
                    $prog['external_programme_id'],
                    $prog['name'],
                    'approved',
                    $prog['commission_value'] . '%',
                    $prog['currency'],
                    $prog['cookie_duration_days'] . ' days',
                ];
                continue;
            }

            $existing = AffiliateProgramme::where('provider_id', $provider->id)
                ->where('external_programme_id', $prog['external_programme_id'])
                ->first();

            $data = [
                'provider_id'           => $provider->id,
                'external_programme_id' => $prog['external_programme_id'],
                'name'                  => $prog['name'],
                'publisher_id'          => $prog['publisher_id'],
                'status'                => 'approved',
                'approved_at'           => $existing?->approved_at ?? now(),
                'commission_type'       => $prog['commission_type'],
                'commission_value'      => $prog['commission_value'],
                'currency'              => $prog['currency'],
                'cookie_duration_days'  => $prog['cookie_duration_days'],
                'epc'                   => $prog['epc'],
                'conversion_rate'       => $prog['conversion_rate'],
                'metrics_updated_at'    => now(),
                'network_metadata'      => $prog['network_metadata'] ?? null,
                'last_synced_at'        => now(),
            ];

            AffiliateProgramme::updateOrCreate(
                [
                    'provider_id'           => $provider->id,
                    'external_programme_id' => $prog['external_programme_id'],
                ],
                $data
            );

            $rows[] = [
                $prog['external_programme_id'],
                $prog['name'],
                'approved',
                $prog['commission_value'] . '%',
                $prog['currency'],
                $prog['cookie_duration_days'] . ' days',
            ];

            if ($existing) {
                $updated++;
            } else {
                $created++;
            }
        }

        $this->table(
            ['Programme ID', 'Name', 'Status', 'Commission', 'Currency', 'Cookie Window'],
            $rows
        );

        if (!$dryRun) {
            $this->info("\nSeeded: {$created} created, {$updated} updated.");
            $this->info('All 5 approved Awin programmes are now in the database with status: APPROVED.');
        }

        return Command::SUCCESS;
    }
}
