<?php

namespace Tests\Feature;

use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductIdentifier;
use App\Models\Retailer;
use App\Services\Quality\DataQualityService;
use App\Services\SEO\SeoEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_eligibility_enforces_active_offer_requirement(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'us')->first();
        $seoService = new SeoEligibilityService();

        // 1. Product without offers is NOT indexable
        $brand = \App\Models\Brand::firstOrCreate(['slug' => 'apple'], ['name' => 'Apple', 'is_active' => true]);
        $category = \App\Models\Category::first();
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Apple Test Product',
            'slug' => 'apple-test-product',
            'status' => 'published',
        ]);
        $this->assertFalse($seoService->isIndexable($product, $market));
        $this->assertEquals('noindex, follow', $seoService->getRobotsDirective($product, $market));

        // 2. Add in-stock offer -> Becomes indexable
        $retailer = Retailer::create([
            'name' => 'Amazon',
            'slug' => 'amazon',
            'domain' => 'amazon.com',
            'is_active' => true,
        ]);

        Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'B0CX23V2ZP',
            'title' => 'Product Offer',
            'affiliate_url' => 'https://amazon.com/dp/B0CX23V2ZP',
            'price' => 1999.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $this->assertTrue($seoService->isIndexable($product, $market));
        $this->assertStringContainsString('index, follow', $seoService->getRobotsDirective($product, $market));
    }

    public function test_data_quality_service_audits_catalog_integrity(): void
    {
        $this->artisan('system:init-foundation');
        $qualityService = new DataQualityService();

        $report = $qualityService->audit();

        $this->assertArrayHasKey('total_issues', $report);
        $this->assertArrayHasKey('conflicting_identifiers_count', $report);
        $this->assertArrayHasKey('stale_offers_count', $report);
    }
}
