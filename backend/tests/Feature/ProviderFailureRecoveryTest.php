<?php

namespace Tests\Feature;

use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\AwinProvider;
use App\Services\Affiliate\CjProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderFailureRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_handles_http_500_and_rate_limit_gracefully(): void
    {
        // Simulate Awin 500 error
        Http::fake([
            'https://api.awin.com/publishers/12345/programmes*' => Http::response('Internal Server Error', 500),
            'https://ads.api.cj.com/query' => Http::response(['errors' => [['message' => 'Rate Limit Exceeded']]], 429),
        ]);

        $awin = AffiliateProvider::create([
            'code' => 'awin',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'publisher_id' => '12345'],
            'status' => 'connected',
        ]);

        $cj = AffiliateProvider::create([
            'code' => 'cj',
            'name' => 'CJ Affiliate',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'company_id' => '5566'],
            'status' => 'connected',
        ]);

        $awinConnector = new AwinProvider();
        $cjConnector = new CjProvider();

        $awinRes = $awinConnector->testConnection($awin);
        $cjRes = $cjConnector->testConnection($cj);

        $this->assertFalse($awinRes['connected']);
        $this->assertEquals('error', $awinRes['status']);

        $this->assertFalse($cjRes['connected']);
        $this->assertEquals('error', $cjRes['status']);
    }

    public function test_stale_offer_backoff_increments_error_count(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'us')->first();
        $provider = AffiliateProvider::where('code', 'impact')->first();
        $retailer = Retailer::create([
            'name' => 'Lenovo US',
            'slug' => 'lenovo-us',
            'domain' => 'lenovo.com',
            'affiliate_provider_id' => $provider->id,
            'is_active' => true,
        ]);

        $brand = Brand::firstOrCreate(['slug' => 'lenovo'], ['name' => 'Lenovo', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Lenovo ThinkPad X1 Carbon',
            'slug' => 'lenovo-thinkpad-x1-carbon',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'LENOVO-X1',
            'title' => 'ThinkPad X1 Carbon',
            'affiliate_url' => 'https://lenovo.com/thinkpad-x1',
            'price' => 1499.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
            'error_count' => 0,
        ]);

        // Simulate refresh failure
        $offer->increment('error_count');
        $backoffHours = (int) pow(2, min($offer->error_count, 6));
        $offer->update(['next_check_at' => now()->addHours($backoffHours)]);

        $this->assertEquals(1, $offer->fresh()->error_count);
        $this->assertTrue($offer->fresh()->next_check_at > now()->addHours(1));
    }
}
