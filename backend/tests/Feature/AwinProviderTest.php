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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AwinProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_connection_reports_not_configured_when_empty(): void
    {
        config(['services.awin.api_token' => null, 'services.awin.publisher_id' => null]);

        $provider = AffiliateProvider::create([
            'code' => 'awin',
            'name' => 'Awin Publisher Network',
            'is_active' => false,
            'config' => null,
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('not_configured', $result['status']);
    }

    public function test_connection_succeeds_when_awin_api_responds(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/12345/programmes*' => Http::response([
                ['id' => 1001, 'name' => 'Currys PC World'],
            ], 200),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => [
                'api_token' => 'VALID_AWIN_TOKEN_123',
                'publisher_id' => '12345',
            ],
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertTrue($result['connected']);
        $this->assertEquals('connected', $result['status']);
    }

    public function test_generate_affiliate_url_builds_valid_awin_cread_link(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'uk')->first();
        $provider = AffiliateProvider::where('code', 'awin')->first();
        $provider->update([
            'config' => [
                'publisher_id' => '998877',
            ],
        ]);

        $retailer = Retailer::create([
            'name' => 'Currys',
            'slug' => 'currys',
            'domain' => 'currys.co.uk',
            'affiliate_provider_id' => $provider->id,
            'affiliate_program_id' => '1599',
            'is_active' => true,
        ]);

        $brand = Brand::firstOrCreate(['slug' => 'dell'], ['name' => 'Dell', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Dell XPS 13',
            'slug' => 'dell-xps-13',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'CURRYS-DELL-13',
            'title' => 'Dell XPS 13 at Currys',
            'affiliate_url' => 'https://currys.co.uk/products/dell-xps-13',
            'original_url' => 'https://currys.co.uk/products/dell-xps-13',
            'price' => 1299.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $connector = new AwinProvider();
        $link = $connector->generateAffiliateUrl($offer, $market, 'clickref99');

        $this->assertStringContainsString('https://www.awin1.com/cread.php', $link);
        $this->assertStringContainsString('awinmid=1599', $link);
        $this->assertStringContainsString('awinaffid=998877', $link);
        $this->assertStringContainsString('clickref=clickref99', $link);
        $this->assertStringContainsString(urlencode('https://currys.co.uk/products/dell-xps-13'), $link);
    }

    public function test_normalize_awin_item_maps_identifiers_and_specs(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'uk')->first();

        $rawItem = [
            'id' => 'AWIN_PROD_100',
            'product_name' => 'Samsung Galaxy S24 Ultra 256GB - Titanium Gray',
            'brand_name' => 'Samsung Electronics',
            'ean' => '8806095301234',
            'mpn' => 'SM-S928B',
            'price' => 1249.00,
            'retail_price' => 1349.00,
            'currency' => 'GBP',
            'in_stock' => true,
            'merchant_name' => 'Samsung UK',
            'merchant_domain' => 'samsung.com',
            'product_url' => 'https://samsung.com/uk/smartphones/galaxy-s24-ultra',
            'image_url' => 'https://images.samsung.com/s24.jpg',
            'specifications' => [
                'Display' => '6.8 inch Dynamic AMOLED 2X',
                'Processor' => 'Snapdragon 8 Gen 3',
            ],
        ];

        $connector = new AwinProvider();
        $dto = $connector->normalizeAwinItem($rawItem, $market);

        $this->assertNotNull($dto);
        $this->assertEquals('Samsung Galaxy S24 Ultra 256GB - Titanium Gray', $dto->name);
        $this->assertEquals('Samsung Electronics', $dto->brandName);
        $this->assertEquals('SM-S928B', $dto->canonicalMpn);
        $this->assertEquals('8806095301234', $dto->canonicalEan);
        $this->assertEquals(1249.00, $dto->offer->price);
        $this->assertEquals('GBP', $dto->offer->currencyCode);
        $this->assertEquals('in_stock', $dto->offer->availability);
        $this->assertCount(2, $dto->specifications);
    }

    public function test_search_products_throws_exception_on_api_error(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'gb')->first();

        Http::fake([
            'https://api.awin.com/publishers/*/productsearch*' => Http::response('Request not allowed by policy', 403),
        ]);

        $provider = AffiliateProvider::where('code', 'awin')->first();
        $provider->update([
            'is_active' => true,
            'config' => ['api_token' => 'TEST_TOKEN', 'publisher_id' => '12345'],
        ]);

        $connector = new AwinProvider();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Awin API HTTP 403 Error');

        $connector->searchProducts('laptop', $market);
    }

    public function test_generate_affiliate_url_returns_target_url_when_no_advertiser_id(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'gb')->first();

        $provider = AffiliateProvider::where('code', 'awin')->first();
        $provider->update([
            'is_active' => true,
            'config' => ['api_token' => 'TEST_TOKEN', 'publisher_id' => '12345'],
        ]);

        $retailer = Retailer::create([
            'name' => 'Unknown Shop',
            'slug' => 'unknown-shop',
            'domain' => 'unknown.co.uk',
            'affiliate_provider_id' => $provider->id,
            'affiliate_program_id' => null, // No program ID configured
            'is_active' => true,
        ]);

        $brand = Brand::firstOrCreate(['slug' => 'dell'], ['name' => 'Dell', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'laptops'], ['name' => 'Laptops', 'is_active' => true]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Test Laptop',
            'slug' => 'test-laptop',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'TEST-SKU-1',
            'title' => 'Test Laptop at Unknown Shop',
            'affiliate_url' => 'https://unknown.co.uk/products/laptop',
            'original_url' => 'https://unknown.co.uk/products/laptop',
            'price' => 999.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $connector = new AwinProvider();
        $url = $connector->generateAffiliateUrl($offer, $market);

        // Must not contain fake 12345 fallback; must return target URL directly
        $this->assertEquals('https://unknown.co.uk/products/laptop', $url);
        $this->assertStringNotContainsString('awinmid=12345', $url);
    }
}

