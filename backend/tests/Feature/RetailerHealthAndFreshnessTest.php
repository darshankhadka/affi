<?php

namespace Tests\Feature;

use App\Models\AffiliateProvider;
use App\Models\Offer;
use App\Models\Retailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetailerHealthAndFreshnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_affiliate_health_check_command_runs_successfully(): void
    {
        $this->artisan('affiliate:health-check')
            ->assertExitCode(0);
    }

    public function test_affiliate_test_provider_command_runs_truthfully(): void
    {
        $this->artisan('affiliate:test-provider', ['provider' => 'awin'])
            ->assertExitCode(0);

        $this->artisan('affiliate:test-provider', ['provider' => 'amazon'])
            ->assertExitCode(0);
    }

    public function test_affiliate_test_retailer_command_inspects_retailer(): void
    {
        $this->artisan('affiliate:test-retailer', ['retailer' => 'amazon-us'])
            ->assertExitCode(0);

        $this->artisan('affiliate:test-retailer', ['retailer' => 'currys-uk'])
            ->assertExitCode(0);
    }

    public function test_automation_sync_market_command_executes_idempotently(): void
    {
        $this->artisan('automation:sync-market', ['market' => 'de', '--dry-run' => true])
            ->assertExitCode(0);
    }
}
