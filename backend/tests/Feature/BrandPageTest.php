<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_endpoint_returns_active_brands_and_product_counts(): void
    {
        $this->artisan('system:init-foundation');

        $brand = Brand::firstOrCreate(['slug' => 'asus'], ['name' => 'Asus', 'is_active' => true]);
        $category = Category::first();

        Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'ASUS ROG Swift OLED PG32UCDM',
            'slug' => 'asus-rog-swift-oled-pg32ucdm',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/brands');
        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);

        $singleRes = $this->getJson('/api/v1/brands/asus');
        $singleRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Asus')
            ->assertJsonPath('data.products_count', 1);

        $missingRes = $this->getJson('/api/v1/brands/non-existent-brand');
        $missingRes->assertStatus(404);
    }
}
