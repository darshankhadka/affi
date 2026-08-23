<?php

namespace Tests\Feature;

use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\CjProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CjProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_reports_not_configured_when_empty(): void
    {
        $provider = AffiliateProvider::create([
            'code' => 'cj',
            'name' => 'CJ Affiliate',
            'is_active' => false,
            'config' => null,
            'status' => 'disconnected',
        ]);

        $connector = new CjProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('not_configured', $result['status']);
    }

    public function test_connection_succeeds_when_graphql_responds(): void
    {
        Http::fake([
            'https://ads.api.cj.com/query' => Http::response([
                'data' => [
                    'publisher' => [
                        'companyName' => 'ARIKARTECH Media',
                        'companyId' => '5566778',
                    ]
                ]
            ], 200),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'cj',
            'name' => 'CJ Affiliate',
            'is_active' => true,
            'config' => [
                'api_token' => 'VALID_CJ_GRAPHQL_TOKEN',
                'company_id' => '5566778',
            ],
            'status' => 'disconnected',
        ]);

        $connector = new CjProvider();
        $result = $connector->testConnection($provider);

        $this->assertTrue($result['connected']);
        $this->assertEquals('connected', $result['status']);
    }

    public function test_generate_affiliate_url_injects_website_id_and_sid(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'us')->first();
        $provider = AffiliateProvider::where('code', 'cj')->first();
        $provider->update([
            'config' => [
                'website_id' => '100500100',
            ],
        ]);

        $retailer = Retailer::create([
            'name' => 'Dell US',
            'slug' => 'dell-us',
            'domain' => 'dell.com',
            'affiliate_provider_id' => $provider->id,
            'affiliate_program_id' => '15500200',
            'is_active' => true,
        ]);

        $brand = Brand::firstOrCreate(['slug' => 'dell'], ['name' => 'Dell', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Dell Alienware m16 R2',
            'slug' => 'dell-alienware-m16-r2',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'DELL-AW-M16',
            'title' => 'Dell Alienware m16 Gaming Laptop',
            'affiliate_url' => 'https://www.dell.com/en-us/shop/alienware-m16',
            'original_url' => 'https://www.dell.com/en-us/shop/alienware-m16',
            'price' => 1899.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $connector = new CjProvider();
        $url = $connector->generateAffiliateUrl($offer, $market, 'cjsub99');

        $this->assertStringContainsString('https://www.anrdoezrs.net/click-100500100-15500200', $url);
        $this->assertStringContainsString('sid=cjsub99', $url);
        $this->assertStringContainsString(urlencode('https://www.dell.com/en-us/shop/alienware-m16'), $url);
    }
}
