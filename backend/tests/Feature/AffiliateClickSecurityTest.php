<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateClickSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_outbound_affiliate_redirect_sets_security_headers_and_logs_click(): void
    {
        $brand = Brand::create(['name' => 'Apple', 'slug' => 'apple']);
        $cat = Category::first();
        $prod = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'MacBook Air M3',
            'slug' => 'macbook-air-m3',
            'status' => 'published',
        ]);
        $market = Market::where('code', 'gb')->first();
        $currency = Currency::where('code', 'GBP')->first();
        $retailer = Retailer::where('slug', 'currys-uk')->first() ?? Retailer::create([
            'name' => 'Currys UK',
            'slug' => 'currys-uk',
            'domain' => 'currys.co.uk',
            'is_active' => true,
        ]);

        $offer = Offer::create([
            'product_id' => $prod->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'sku' => 'SKU_MBA_1',
            'title' => 'MacBook Air M3 256GB',
            'affiliate_url' => 'https://www.awin1.com/pclick.php?p=99999',
            'original_url' => 'https://www.currys.co.uk/products/macbook-air.html',
            'price' => 999.00,
            'is_active' => true,
        ]);

        $response = $this->get("/api/v1/affiliates/out/{$offer->id}?subid=banner_top");

        $response->assertStatus(302);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->assertDatabaseHas('affiliate_clicks', [
            'offer_id' => $offer->id,
            'product_id' => $prod->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
        ]);
    }

    public function test_inactive_or_missing_offer_redirect_fails_gracefully(): void
    {
        $response = $this->get('/api/v1/affiliates/out/999999');
        $response->assertStatus(404);
    }
}
