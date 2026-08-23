<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_catalog_artisan_commands_execute_cleanly(): void
    {
        $this->artisan('system:init-foundation');

        $this->artisan('catalog:health')
            ->expectsOutputToContain('CATALOG HEALTH & INTEGRITY AUDIT')
            ->assertExitCode(0);

        $this->artisan('catalog:stats')
            ->expectsOutputToContain('CATALOG & MONETIZATION STATISTICS')
            ->assertExitCode(0);

        $this->artisan('catalog:validate')
            ->expectsOutputToContain('CATALOG DEEP VALIDATION')
            ->assertExitCode(0);

        $this->artisan('catalog:quality')
            ->expectsOutputToContain('CATALOG QUALITY SCORE AUDIT')
            ->assertExitCode(0);

        $this->artisan('catalog:seo-audit')
            ->expectsOutputToContain('CATALOG SEO & SITEMAP AUDIT')
            ->assertExitCode(0);

        $this->artisan('catalog:conversion-stats')
            ->expectsOutputToContain('CONVERSION FUNNEL & REVENUE STATS')
            ->assertExitCode(0);
    }
}
