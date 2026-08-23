<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_health_check_endpoint_reports_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'php_version',
                    'database_connected',
                    'cache_accessible',
                    'stale_offers_count',
                    'providers',
                ],
            ]);

        $this->assertEquals('healthy', $response->json('data.status'));
        $this->assertTrue($response->json('data.database_connected'));
    }
}
