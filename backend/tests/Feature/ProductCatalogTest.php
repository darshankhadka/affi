<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Product;
use App\Models\ProductIdentifier;
use App\Services\Matching\ProductMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_empty_catalog_returns_empty_paginated_list_with_zero_count(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
    }

    public function test_product_matching_resolves_indexed_identifiers(): void
    {
        $brand = Brand::create(['name' => 'Apple', 'slug' => 'apple']);
        $cat = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'MacBook Pro 14 M3',
            'slug' => 'macbook-pro-14-m3',
            'model_number' => 'MRX33LL/A',
            'status' => 'published',
        ]);

        $matchingService = app(ProductMatchingService::class);
        $matchingService->registerIdentifier($product, 'UPC', '195949123456');
        $matchingService->registerIdentifier($product, 'ASIN', 'B0CM5NXYZ1');

        // Test UPC matching
        $match1 = $matchingService->match(['identifiers' => ['UPC' => '195949123456']]);
        $this->assertNotNull($match1['product']);
        $this->assertEquals($product->id, $match1['product']->id);
        $this->assertEquals('identifier:UPC', $match1['match_type']);

        // Test ASIN matching
        $match2 = $matchingService->match(['identifiers' => ['ASIN' => 'B0CM5NXYZ1']]);
        $this->assertNotNull($match2['product']);
        $this->assertEquals($product->id, $match2['product']->id);

        // Test Brand + Model matching
        $match3 = $matchingService->match(['brand_name' => 'Apple', 'model_number' => 'MRX33LL/A']);
        $this->assertNotNull($match3['product']);
        $this->assertEquals($product->id, $match3['product']->id);
        $this->assertEquals('brand_model', $match3['match_type']);
    }

    public function test_product_detail_endpoint_returns_seo_and_structured_data(): void
    {
        $brand = Brand::create(['name' => 'AMD', 'slug' => 'amd']);
        $cat = Category::where('slug', 'cpus')->first() ?? Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'AMD Ryzen 7 7800X3D',
            'slug' => 'amd-ryzen-7-7800x3d',
            'status' => 'published',
            'short_description' => 'Top gaming processor with 3D V-Cache.',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->slug}?market=us");

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'amd-ryzen-7-7800x3d')
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'seo' => ['title', 'description', 'canonical', 'robots'],
                    'structured_data' => ['@context', '@type', 'name', 'brand'],
                ],
            ]);
    }

    public function test_cors_headers_are_returned_for_allowed_origins(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:3000',
        ])->getJson('/api/v1/products?market=gb');

        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
    }

    public function test_cors_preflight_options_request_succeeds(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://arikartech.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'content-type, accept',
        ])->options('/api/v1/products?market=gb');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'https://arikartech.com');
        $response->assertHeader('Access-Control-Allow-Methods', 'GET');
    }

    public function test_products_endpoint_filters_by_market_offers(): void
    {
        $brand = Brand::create(['name' => 'Logitech', 'slug' => 'logitech']);
        $cat = Category::first();
        $marketGb = Market::where('code', 'gb')->first();
        $marketDe = Market::where('code', 'de')->first();
        $currencyGbp = \App\Models\Currency::where('code', 'GBP')->first();
        $currencyEur = \App\Models\Currency::where('code', 'EUR')->first();

        // Product 1 with GB offer
        $prodGb = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'Logitech Mouse GB',
            'slug' => 'logitech-mouse-gb',
            'status' => 'published',
        ]);
        $retailerGb = \App\Models\Retailer::create(['name' => 'Currys', 'slug' => 'currys', 'domain' => 'currys.co.uk', 'is_active' => true]);
        \App\Models\Offer::create([
            'product_id' => $prodGb->id,
            'retailer_id' => $retailerGb->id,
            'market_id' => $marketGb->id,
            'currency_id' => $currencyGbp->id,
            'price' => 19.99,
            'sku' => 'SKU_GB_1',
            'title' => 'Logitech Mouse GB',
            'affiliate_url' => 'https://www.awin1.com/pclick.php?p=1',
            'is_active' => true,
        ]);

        // Product 2 with DE offer
        $prodDe = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'Logitech Mouse DE',
            'slug' => 'logitech-mouse-de',
            'status' => 'published',
        ]);
        $retailerDe = \App\Models\Retailer::create(['name' => 'Otto', 'slug' => 'otto', 'domain' => 'otto.de', 'is_active' => true]);
        \App\Models\Offer::create([
            'product_id' => $prodDe->id,
            'retailer_id' => $retailerDe->id,
            'market_id' => $marketDe->id,
            'currency_id' => $currencyEur->id,
            'price' => 24.99,
            'sku' => 'SKU_DE_1',
            'title' => 'Logitech Mouse DE',
            'affiliate_url' => 'https://www.awin1.com/pclick.php?p=2',
            'is_active' => true,
        ]);

        // Query GB market -> should return only Product 1
        $resGb = $this->getJson('/api/v1/products?market=gb');
        $resGb->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'logitech-mouse-gb');

        // Query DE market -> should return only Product 2
        $resDe = $this->getJson('/api/v1/products?market=de');
        $resDe->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'logitech-mouse-de');
    }
}
