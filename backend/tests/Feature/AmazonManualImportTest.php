<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\Affiliate\AmazonManualImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmazonManualImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Market $usMarket;
    protected Market $ukMarket;
    protected Market $deMarket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');

        $this->adminUser = User::where('email', 'admin@arikartech.com')->first();
        $this->usMarket = Market::where('code', 'us')->first();
        $this->ukMarket = Market::where('code', 'gb')->first() ?? Market::where('code', 'uk')->first();
        $this->deMarket = Market::where('code', 'de')->first();
    }

    public function test_amazon_url_parser_extracts_asin_and_market_accurately(): void
    {
        $service = app(AmazonManualImportService::class);

        // US standard URL
        $resUs = $service->validateAndParseUrl('https://www.amazon.com/Apple-MacBook-14-inch-10-core-Unified/dp/B0CX23V2ZP/ref=sr_1_1');
        $this->assertTrue($resUs['valid']);
        $this->assertEquals('B0CX23V2ZP', $resUs['asin']);
        $this->assertEquals('us', $resUs['market_code']);
        $this->assertStringContainsString('tag=arikartech-20', $resUs['monetized_url']);

        // UK URL
        $resUk = $service->validateAndParseUrl('https://www.amazon.co.uk/dp/B0CX23V2ZP?th=1');
        $this->assertTrue($resUk['valid']);
        $this->assertEquals('B0CX23V2ZP', $resUk['asin']);
        $this->assertEquals('gb', $resUk['market_code']);
        $this->assertStringContainsString('tag=arikartechuk-21', $resUk['monetized_url']);

        // DE URL
        $resDe = $service->validateAndParseUrl('https://www.amazon.de/gp/product/B0CX23V2ZP');
        $this->assertTrue($resDe['valid']);
        $this->assertEquals('B0CX23V2ZP', $resDe['asin']);
        $this->assertEquals('de', $resDe['market_code']);
        $this->assertStringContainsString('tag=arikartechde-21', $resDe['monetized_url']);
    }

    public function test_amazon_url_parser_rejects_invalid_url(): void
    {
        $service = app(AmazonManualImportService::class);

        $res = $service->validateAndParseUrl('https://example.com/not-amazon');
        $this->assertFalse($res['valid']);
        $this->assertNotNull($res['error']);
    }

    public function test_manual_amazon_import_creates_canonical_product_and_offer(): void
    {
        $service = app(AmazonManualImportService::class);

        $importData = [
            'url' => 'https://www.amazon.com/dp/B0CX23V2ZP',
            'name' => 'Apple MacBook Pro 14 M3 Max',
            'price' => 2499.00,
            'original_price' => 2699.00,
            'brand_name' => 'Apple',
            'model_number' => 'MRX33LL/A',
            'category_slug' => 'laptops',
            'market_code' => 'us',
            'currency_code' => 'USD',
            'availability' => 'in_stock',
            'condition' => 'new',
            'upc' => '195949112233',
        ];

        $result = $service->import($importData);

        $this->assertTrue($result['success']);
        $this->assertEquals('created_product', $result['action']);

        // Verify product in database
        $this->assertDatabaseHas('products', [
            'name' => 'Apple MacBook Pro 14 M3 Max',
            'model_number' => 'MRX33LL/A',
            'canonical_upc' => '195949112233',
        ]);

        // Verify Amazon offer in database
        $this->assertDatabaseHas('offers', [
            'sku' => 'B0CX23V2ZP',
            'price' => 2499.00,
            'is_active' => true,
        ]);

        // Verify product has best price computed
        $product = Product::where('slug', $result['product']->slug)->first();
        $this->assertNotNull($product);
    }

    public function test_manual_amazon_import_attaches_to_existing_canonical_product_by_asin(): void
    {
        $service = app(AmazonManualImportService::class);

        // Initial product created
        $service->import([
            'url' => 'https://www.amazon.com/dp/B0CX23V2ZP',
            'name' => 'Apple MacBook Pro 14 M3 Max',
            'price' => 2499.00,
            'brand_name' => 'Apple',
            'market_code' => 'us',
        ]);

        $initialProductCount = Product::count();

        // Second import of same ASIN in UK market
        $resultUk = $service->import([
            'url' => 'https://www.amazon.co.uk/dp/B0CX23V2ZP',
            'name' => 'Apple MacBook Pro 14 (UK)',
            'price' => 2199.00,
            'brand_name' => 'Apple',
            'market_code' => 'uk',
            'currency_code' => 'GBP',
        ]);

        $this->assertTrue($resultUk['success']);
        $this->assertEquals('matched_existing', $resultUk['action']);
        $this->assertEquals($initialProductCount, Product::count(), 'Should not duplicate canonical product');

        // Verify both US and UK offers exist for the same canonical product
        $this->assertEquals(2, Offer::where('product_id', $resultUk['product']->id)->count());
    }

    public function test_admin_api_endpoints_for_amazon_import(): void
    {
        // 1. Validate endpoint
        $valRes = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/affiliates/amazon/validate-url', [
                'url' => 'https://www.amazon.de/dp/B08N5WRWNW',
            ]);

        $valRes->assertOk();
        $valRes->assertJsonPath('data.asin', 'B08N5WRWNW');
        $valRes->assertJsonPath('data.market_code', 'de');

        // 2. Import endpoint
        $impRes = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/affiliates/amazon/import', [
                'url' => 'https://www.amazon.de/dp/B08N5WRWNW',
                'name' => 'Sony WH-1000XM4 Wireless Headphones',
                'price' => 279.00,
                'brand_name' => 'Sony',
                'market_code' => 'de',
                'currency_code' => 'EUR',
            ]);

        $impRes->assertStatus(201);
        $impRes->assertJsonPath('data.offer.sku', 'B08N5WRWNW');
    }
}
