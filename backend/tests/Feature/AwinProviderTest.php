<?php

namespace Tests\Feature;

use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\AwinDatafeedService;
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
                [
                    'id' => 25962,
                    'name' => 'BlazeVideo DE',
                    'displayUrl' => 'https://www.blazevideos.de/',
                    'currencyCode' => 'EUR',
                    'primaryRegion' => ['countryCode' => 'DE', 'name' => 'Germany'],
                    'status' => 'Active',
                    'linkStatus' => 'online',
                ],
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
        $this->assertEquals(1, $result['programmes_count']);
    }

    public function test_get_joined_programmes_discovers_advertisers_successfully(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/12345/programmes*' => Http::response([
                [
                    'id' => 25962,
                    'name' => 'BlazeVideo DE',
                    'displayUrl' => 'https://www.blazevideos.de/',
                    'clickThroughUrl' => 'https://www.awin1.com/awclick.php?mid=25962&id=12345',
                    'currencyCode' => 'EUR',
                    'primaryRegion' => ['countryCode' => 'DE', 'name' => 'Germany'],
                    'primarySector' => 'Electronic Accessories',
                    'status' => 'Active',
                    'linkStatus' => 'online',
                ],
            ], 200),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'publisher_id' => '12345'],
            'status' => 'connected',
        ]);

        $connector = new AwinProvider();
        $programmes = $connector->getJoinedProgrammes($provider);

        $this->assertCount(1, $programmes);
        $this->assertEquals(25962, $programmes[0]['id']);
        $this->assertEquals('BlazeVideo DE', $programmes[0]['name']);
        $this->assertEquals('DE', $programmes[0]['primaryRegion']['countryCode']);
        $this->assertEquals('EUR', $programmes[0]['currencyCode']);
    }

    public function test_generate_affiliate_url_builds_valid_awin_cread_link(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'de')->first();
        $provider = AffiliateProvider::where('code', 'awin')->first();
        $provider->update([
            'config' => [
                'publisher_id' => '3053247',
            ],
        ]);

        $retailer = Retailer::create([
            'name' => 'BlazeVideo DE',
            'slug' => 'blazevideo-de',
            'domain' => 'blazevideos.de',
            'affiliate_provider_id' => $provider->id,
            'affiliate_program_id' => '25962',
            'is_active' => true,
        ]);

        $brand = Brand::firstOrCreate(['slug' => 'blazevideo'], ['name' => 'BlazeVideo', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'BlazeVideo A280 Trail Camera',
            'slug' => 'blazevideo-a280-trail-camera',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'BV-A280',
            'title' => 'BlazeVideo A280 Trail Camera 32MP 1296P',
            'affiliate_url' => 'https://www.blazevideos.de/products/a280',
            'original_url' => 'https://www.blazevideos.de/products/a280',
            'price' => 79.99,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $connector = new AwinProvider();
        $link = $connector->generateAffiliateUrl($offer, $market, 'subref42');

        $this->assertStringContainsString('https://www.awin1.com/cread.php', $link);
        $this->assertStringContainsString('awinmid=25962', $link);
        $this->assertStringContainsString('awinaffid=3053247', $link);
        $this->assertStringContainsString('clickref=subref42', $link);
        $this->assertStringContainsString(urlencode('https://www.blazevideos.de/products/a280'), $link);
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

    public function test_normalize_awin_item_maps_feed_columns_properly(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'de')->first();

        $rawItem = [
            'aw_product_id' => 'BV_CAM_101',
            'product_name' => 'BlazeVideo A350 Wildkamera 48MP 4K',
            'brand_name' => 'BlazeVideo',
            'ean' => '0712345678901',
            'mpn' => 'BV-A350',
            'search_price' => '89.99',
            'currency' => 'EUR',
            'in_stock' => '1',
            'merchant_name' => 'BlazeVideo DE',
            'merchant_domain' => 'blazevideos.de',
            'aw_deep_link' => 'https://www.awin1.com/pclick.php?p=101',
            'merchant_deep_link' => 'https://www.blazevideos.de/products/a350',
            'merchant_image_url' => 'https://blazevideos.de/images/a350.jpg',
            'description' => 'Hochwertige 4K Wildkamera mit Nachtsicht',
        ];

        $connector = new AwinProvider();
        $dto = $connector->normalizeAwinItem($rawItem, $market);

        $this->assertNotNull($dto);
        $this->assertEquals('BlazeVideo A350 Wildkamera 48MP 4K', $dto->name);
        $this->assertEquals('BlazeVideo', $dto->brandName);
        $this->assertEquals('BV-A350', $dto->canonicalMpn);
        $this->assertEquals('0712345678901', $dto->canonicalEan);
        $this->assertEquals(89.99, $dto->offer->price);
        $this->assertEquals('EUR', $dto->offer->currencyCode);
        $this->assertEquals('in_stock', $dto->offer->availability);
        $this->assertEquals('https://www.awin1.com/pclick.php?p=101', $dto->offer->affiliateUrl);
        $this->assertEquals('https://www.blazevideos.de/products/a350', $dto->offer->originalUrl);
    }

    public function test_search_products_downloads_and_parses_feed_successfully(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'de')->first();

        $csvFeed = "aw_product_id,product_name,description,search_price,merchant_image_url,aw_deep_link,merchant_deep_link,ean,upc,mpn,brand_name,merchant_name,merchant_id,category_name,in_stock,delivery_cost,currency\n" .
            "1001,BlazeVideo A280 Trail Camera,Top night vision camera,79.99,https://blazevideos.de/a280.jpg,https://awin1.com/p1,https://blazevideos.de/p1,0711122233344,,A280,BlazeVideo,BlazeVideo DE,25962,Cameras,1,0.00,EUR\n" .
            "1002,BlazeVideo Solar Panel Kit,Solar charger for trail cams,39.99,https://blazevideos.de/solar.jpg,https://awin1.com/p2,https://blazevideos.de/p2,0711122233355,,SOLAR-1,BlazeVideo,BlazeVideo DE,25962,Accessories,1,0.00,EUR\n";

        Http::fake([
            'https://api.awin.com/publishers/12345/programmes*' => Http::response([
                [
                    'id' => 25962,
                    'name' => 'BlazeVideo DE',
                    'displayUrl' => 'https://www.blazevideos.de/',
                    'currencyCode' => 'EUR',
                    'primaryRegion' => ['countryCode' => 'DE', 'name' => 'Germany'],
                    'status' => 'Active',
                    'linkStatus' => 'online',
                ],
            ], 200),
            'https://productdata.awin.com/datafeed/download/*' => Http::response($csvFeed, 200, [
                'Content-Type' => 'text/csv',
            ]),
        ]);

        $provider = AffiliateProvider::where('code', 'awin')->first();
        $provider->update([
            'is_active' => true,
            'config' => ['api_token' => 'TEST_TOKEN', 'publisher_id' => '12345'],
        ]);

        $connector = new AwinProvider();
        $products = $connector->searchProducts('camera', $market, null, 10);

        $this->assertCount(1, $products);
        $this->assertEquals('BlazeVideo A280 Trail Camera', $products[0]->name);
        $this->assertEquals(79.99, $products[0]->offer->price);
        $this->assertEquals('EUR', $products[0]->offer->currencyCode);
    }

    public function test_search_products_handles_gzip_feed_content(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'de')->first();

        $csvFeed = "aw_product_id,product_name,description,search_price,merchant_image_url,aw_deep_link,merchant_deep_link,ean,upc,mpn,brand_name,merchant_name,merchant_id,category_name,in_stock,delivery_cost,currency\n" .
            "2001,BlazeVideo 4K Trail Cam,Ultra HD game camera,119.99,https://blazevideos.de/4k.jpg,https://awin1.com/p3,https://blazevideos.de/p3,0711122233366,,A350-4K,BlazeVideo,BlazeVideo DE,25962,Cameras,1,0.00,EUR\n";

        $gzipped = gzencode($csvFeed);

        Http::fake([
            'https://api.awin.com/publishers/12345/programmes*' => Http::response([
                [
                    'id' => 25962,
                    'name' => 'BlazeVideo DE',
                    'displayUrl' => 'https://www.blazevideos.de/',
                    'currencyCode' => 'EUR',
                    'primaryRegion' => ['countryCode' => 'DE', 'name' => 'Germany'],
                    'status' => 'Active',
                    'linkStatus' => 'online',
                ],
            ], 200),
            'https://productdata.awin.com/datafeed/download/*' => Http::response($gzipped, 200, [
                'Content-Type' => 'application/x-gzip',
            ]),
        ]);

        $provider = AffiliateProvider::where('code', 'awin')->first();
        $provider->update([
            'is_active' => true,
            'config' => ['api_token' => 'TEST_TOKEN', 'publisher_id' => '12345'],
        ]);

        $connector = new AwinProvider();
        $products = $connector->searchProducts('Trail Cam', $market, null, 10);

        $this->assertCount(1, $products);
        $this->assertEquals('BlazeVideo 4K Trail Cam', $products[0]->name);
        $this->assertEquals(119.99, $products[0]->offer->price);
    }

    public function test_connection_classifies_401_as_invalid_credentials(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/*/programmes*' => Http::response('Unauthorized', 401),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin_401',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'BAD_TOKEN', 'publisher_id' => '12345'],
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('invalid_credentials', $result['status']);
    }

    public function test_connection_classifies_403_as_endpoint_not_permitted(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/*/programmes*' => Http::response('Forbidden policy', 403),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin_403',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'publisher_id' => '12345'],
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('endpoint_not_permitted', $result['status']);
    }

    public function test_connection_classifies_404_as_endpoint_not_found(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/*/programmes*' => Http::response('Not Found', 404),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin_404',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'publisher_id' => '12345'],
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('endpoint_not_found', $result['status']);
    }

    public function test_connection_classifies_429_as_rate_limited(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/*/programmes*' => Http::response('Too Many Requests', 429),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin_429',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'publisher_id' => '12345'],
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('rate_limited', $result['status']);
    }

    public function test_connection_classifies_500_as_provider_server_error(): void
    {
        Http::fake([
            'https://api.awin.com/publishers/*/programmes*' => Http::response('Internal Server Error', 500),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'awin_500',
            'name' => 'Awin Publisher Network',
            'is_active' => true,
            'config' => ['api_token' => 'TOKEN', 'publisher_id' => '12345'],
            'status' => 'disconnected',
        ]);

        $connector = new AwinProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('provider_server_error', $result['status']);
    }
}
