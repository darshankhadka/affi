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
}
