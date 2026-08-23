<?php

namespace Tests\Feature;

use App\Models\AffiliateClick;
use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateClickAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_affiliate_redirect_logs_click_and_redirects(): void
    {
        $brand = Brand::create(['name' => 'Corsair', 'slug' => 'corsair']);
        $cat = Category::first();
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'Corsair Vengeance DDR5 32GB',
            'slug' => 'corsair-vengeance-ddr5-32gb',
            'status' => 'published',
        ]);

        $market = Market::where('code', 'us')->first();
        $currency = Currency::where('code', 'USD')->first();
        $provider = AffiliateProvider::where('code', 'amazon')->first();
        $retailer = Retailer::create([
            'name' => 'Amazon US',
            'slug' => 'amazon-us',
            'domain' => 'amazon.com',
            'affiliate_provider_id' => $provider->id,
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'Corsair Vengeance DDR5 32GB',
            'affiliate_url' => 'https://www.amazon.com/dp/B0B15DST2J',
            'price' => 119.99,
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        // Trigger outbound redirect
        $response = $this->get("/api/v1/affiliates/out/{$offer->id}");

        $response->assertStatus(302);
        $this->assertEquals(1, AffiliateClick::where('offer_id', $offer->id)->count());

        $click = AffiliateClick::where('offer_id', $offer->id)->first();
        $this->assertNotEmpty($click->ip_hash);
        $this->assertEquals($product->id, $click->product_id);
    }
}
