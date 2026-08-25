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
use App\Models\ProductImage;
use App\Models\Retailer;
use App\Services\Taxonomy\CategoryClassifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTaxonomyAndAffiliateQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_category_classifier_differentiates_laptops_from_bags_and_accessories(): void
    {
        $classifier = new CategoryClassifierService();
        $classifier->ensureTaxonomy();

        $laptop = $classifier->classify(['name' => 'Lenovo ThinkPad T14 Gen 5 Laptop Intel Ultra 7']);
        $this->assertEquals('laptops', $laptop['category_slug']);
        $this->assertGreaterThanOrEqual(0.90, $laptop['confidence']);

        $bag = $classifier->classify(['name' => '15.6 Inch Polyester Laptop Sleeve Bag for Men']);
        $this->assertEquals('laptop-bags-cases', $bag['category_slug']);
        $this->assertGreaterThanOrEqual(0.90, $bag['confidence']);

        $cooler = $classifier->classify(['name' => 'Laptop Stand Cooling Pad RGB Fans']);
        $this->assertEquals('laptop-accessories', $cooler['category_slug']);
        $this->assertGreaterThanOrEqual(0.90, $cooler['confidence']);

        $screen = $classifier->classify(['name' => 'NothingProjector 100-inch ALR Motorized Floor Rising Screen']);
        $this->assertEquals('projectors-screens', $screen['category_slug']);
        $this->assertGreaterThanOrEqual(0.90, $screen['confidence']);

        $tyre = $classifier->classify(['name' => 'Pirelli Diablo Rosso III ( 190/55 ZR17 TL (75W) Baghjul, M/C )']);
        $this->assertEquals('tyres-automotive', $tyre['category_slug']);
        $this->assertGreaterThanOrEqual(0.90, $tyre['confidence']);
    }

    public function test_category_endpoint_filters_strictly_by_category(): void
    {
        $classifier = new CategoryClassifierService();
        $classifier->ensureTaxonomy();

        $brand = Brand::create(['name' => 'TechBrand', 'slug' => 'techbrand']);
        $market = Market::where('code', 'de')->first();
        $currency = Currency::where('code', 'EUR')->first();
        $provider = AffiliateProvider::where('code', 'awin')->first();
        $retailer = Retailer::create([
            'name' => 'Geekbuying DE',
            'slug' => 'geekbuying-de',
            'domain' => 'geekbuying.com',
            'affiliate_provider_id' => $provider->id,
        ]);

        $laptopCat = Category::where('slug', 'laptops')->first();
        $bagCat = Category::where('slug', 'laptop-bags-cases')->first();

        $laptopProduct = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $laptopCat->id,
            'name' => 'Real Pro Laptop 15.6 Inch',
            'slug' => 'real-pro-laptop-156-inch',
            'status' => 'published',
        ]);

        $bagProduct = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $bagCat->id,
            'name' => 'Polyester Laptop Backpack Case',
            'slug' => 'polyester-laptop-backpack-case',
            'status' => 'published',
        ]);

        Offer::create([
            'product_id' => $laptopProduct->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'Real Pro Laptop 15.6 Inch Offer',
            'affiliate_url' => 'https://www.awin1.com/cread.php?awinmid=57897&awinaffid=3053247&ued=https%3A%2F%2Fgeekbuying.com%2Fitem%2F1',
            'price' => 799.00,
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        Offer::create([
            'product_id' => $bagProduct->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'Polyester Laptop Backpack Case Offer',
            'affiliate_url' => 'https://www.awin1.com/cread.php?awinmid=57897&awinaffid=3053247&ued=https%3A%2F%2Fgeekbuying.com%2Fitem%2F2',
            'price' => 29.99,
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        // Filter /products?category=laptops
        $response = $this->getJson('/api/v1/products?category=laptops&market=de');
        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Real Pro Laptop 15.6 Inch', $data[0]['name']);
        $this->assertEquals('laptops', $data[0]['category']['slug']);

        // Check /categories/laptops/products
        $catResponse = $this->getJson('/api/v1/categories/laptops/products?market=de');
        $catResponse->assertStatus(200);
        $this->assertCount(1, $catResponse->json('data'));
        $this->assertEquals('Real Pro Laptop 15.6 Inch', $catResponse->json('data.0.name'));
    }

    public function test_affiliate_redirect_security_and_status_codes(): void
    {
        $brand = Brand::create(['name' => 'BrandTest', 'slug' => 'brandtest']);
        $cat = Category::first();
        $market = Market::where('code', 'de')->first();
        $currency = Currency::where('code', 'EUR')->first();
        $provider = AffiliateProvider::where('code', 'awin')->first();
        $retailer = Retailer::create([
            'name' => 'Retailer DE',
            'slug' => 'retailer-de',
            'domain' => 'retailer.de',
            'affiliate_provider_id' => $provider->id,
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'Active Test Product',
            'slug' => 'active-test-product',
            'status' => 'published',
        ]);

        $activeOffer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'Active Offer Title',
            'affiliate_url' => 'https://www.awin1.com/cread.php?awinmid=123&awinaffid=456&ued=https%3A%2F%2Fstore.com%2Fitem',
            'price' => 199.99,
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $inactiveOffer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'Inactive Offer Title',
            'affiliate_url' => 'https://www.awin1.com/cread.php?awinmid=123&awinaffid=456&ued=https%3A%2F%2Fstore.com%2Fitem2',
            'price' => 199.99,
            'availability' => 'out_of_stock',
            'is_active' => false,
        ]);

        // 1. Active offer returns 302 redirect with security headers
        $response = $this->get("/go/{$activeOffer->id}");
        $response->assertStatus(302);
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertEquals(1, AffiliateClick::where('offer_id', $activeOffer->id)->count());

        // 2. Inactive offer returns 404
        $inactiveResponse = $this->get("/go/{$inactiveOffer->id}");
        $inactiveResponse->assertStatus(404);

        // 3. Non-existent offer returns 404
        $missingResponse = $this->get('/go/999999');
        $missingResponse->assertStatus(404);
    }

    public function test_catalog_audit_and_reclassify_commands_execute(): void
    {
        $this->artisan('catalog:reclassify', ['--dry-run' => true])->assertSuccessful();
        $this->artisan('catalog:audit')->assertSuccessful();
    }
}
