<?php

namespace Tests\Feature;

use App\Models\AffiliateClick;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_analytics_endpoints_return_search_intelligence_and_conversion(): void
    {
        $this->artisan('system:init-foundation');

        $admin = User::where('email', 'admin@arikartech.com')->first();
        $this->actingAs($admin, 'sanctum');

        $market = Market::where('code', 'us')->first();

        // 1. Seed search queries (1 hit, 1 zero-result)
        SearchLog::create([
            'query' => 'RTX 5090 laptop',
            'market_id' => $market->id,
            'results_count' => 5,
            'ip_hash' => 'dummy_hash_1',
            'created_at' => now(),
        ]);

        SearchLog::create([
            'query' => 'RTX 5080 Super',
            'market_id' => $market->id,
            'results_count' => 0,
            'ip_hash' => 'dummy_hash_2',
            'created_at' => now(),
        ]);

        // Search Intelligence Test
        $searchRes = $this->getJson('/api/v1/admin/analytics/search-intelligence');
        $searchRes->assertStatus(200)
            ->assertJsonPath('data.total_searches', 2)
            ->assertJsonPath('data.zero_result_searches_count', 1);

        // Conversion Overview Test
        $conversionRes = $this->getJson('/api/v1/admin/analytics/conversion');
        $conversionRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'metrics' => ['total_clicks', 'clicks_today', 'total_searches', 'ctr_percentage'],
                    'clicks_by_provider',
                    'clicks_by_retailer',
                    'clicks_by_market',
                    'clicks_by_product',
                ],
            ]);
    }
}
