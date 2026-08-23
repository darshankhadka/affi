<?php

namespace Tests\Feature;

use App\Models\Market;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_readiness_command_executes_successfully(): void
    {
        $this->artisan('system:init-foundation');

        $this->artisan('system:production-readiness')
            ->expectsOutputToContain('PRODUCTION READINESS AUDIT')
            ->expectsOutputToContain('Database Connectivity')
            ->expectsOutputToContain('Target Markets Configured')
            ->expectsOutputToContain('Canonical Tech Taxonomy')
            ->expectsOutputToContain('Affiliate Drivers Registered')
            ->assertExitCode(0);
    }
}
