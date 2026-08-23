<?php

namespace Tests\Feature;

use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\ImpactProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImpactProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_reports_not_configured_when_empty(): void
    {
        $provider = AffiliateProvider::create([
            'code' => 'impact',
            'name' => 'Impact.com',
            'is_active' => false,
            'config' => null,
            'status' => 'disconnected',
        ]);

        $connector = new ImpactProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('not_configured', $result['status']);
    }

    public function test_connection_succeeds_when_impact_api_responds(): void
    {
        Http::fake([
            'https://api.impact.com/Mediapartners/IR12345/Campaigns' => Http::response([
                'Campaigns' => [
                    ['CampaignId' => 1001, 'CampaignName' => 'Razer Store'],
                ]
            ], 200),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'impact',
            'name' => 'Impact.com',
            'is_active' => true,
            'config' => [
                'account_sid' => 'IR12345',
                'auth_token' => 'VALID_AUTH_TOKEN_XYZ',
            ],
            'status' => 'disconnected',
        ]);

        $connector = new ImpactProvider();
        $result = $connector->testConnection($provider);

        $this->assertTrue($result['connected']);
        $this->assertEquals('connected', $result['status']);
    }

    public function test_generate_affiliate_url_injects_media_partner_and_subid(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'us')->first();
        $provider = AffiliateProvider::where('code', 'impact')->first();
        $provider->update([
            'config' => [
                'media_partner_id' => '2500100',
            ],
        ]);

        $retailer = Retailer::create([
            'name' => 'Razer Store',
            'slug' => 'razer-store',
            'domain' => 'razer.com',
            'affiliate_provider_id' => $provider->id,
            'affiliate_program_id' => '12000',
            'is_active' => true,
        ]);

        $brand = Brand::firstOrCreate(['slug' => 'razer'], ['name' => 'Razer', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Razer Blade 16',
            'slug' => 'razer-blade-16',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'RZ-BLADE-16',
            'title' => 'Razer Blade 16 Gaming Laptop',
            'affiliate_url' => 'https://www.razer.com/gaming-laptops/razer-blade-16',
            'original_url' => 'https://www.razer.com/gaming-laptops/razer-blade-16',
            'price' => 2999.99,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $connector = new ImpactProvider();
        $url = $connector->generateAffiliateUrl($offer, $market, 'impactsub123');

        $this->assertStringContainsString('https://impact.sjv.io/c/2500100/12000/1234', $url);
        $this->assertStringContainsString('subId1=impactsub123', $url);
        $this->assertStringContainsString(urlencode('https://www.razer.com/gaming-laptops/razer-blade-16'), $url);
    }
}
