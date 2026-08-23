<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductIdentifier;
use App\Models\ProductSpecification;
use App\Models\Retailer;
use App\Services\Quality\DataQualityService;
use App\Services\SEO\SeoEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataQualityScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_score_calculation_is_deterministic(): void
    {
        $this->artisan('system:init-foundation');

        $brand = Brand::firstOrCreate(['slug' => 'lenovo'], ['name' => 'Lenovo', 'is_active' => true]);
        $category = Category::first();
        $market = Market::where('code', 'us')->first();

        // 1. Bare product (Name only)
        $bareProduct = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Lenovo Legion 7i',
            'slug' => 'lenovo-legion-7i',
            'status' => 'published',
        ]);

        $qualityService = app(DataQualityService::class);
        $bareScore = $qualityService->calculateQualityScore($bareProduct);

        // Name (10) + Brand (10) = 20 pts
        $this->assertEquals(20, $bareScore);
        $this->assertEquals('Not Publishable', $qualityService->getQualityGrade($bareScore));

        // 2. Add Model Number (+10) and Identifier (+20)
        $bareProduct->update(['model_number' => '16ITHg6']);
        ProductIdentifier::create([
            'product_id' => $bareProduct->id,
            'type' => 'MPN',
            'value' => '82K6005RUS',
            'normalized_value' => '82K6005RUS',
        ]);

        $scoreWithId = $qualityService->calculateQualityScore($bareProduct->fresh());
        // 20 + 10 + 20 = 50 pts
        $this->assertEquals(50, $scoreWithId);

        // 3. Add Specs (+15), Offer (+15), and Description (+5)
        ProductSpecification::create([
            'product_id' => $bareProduct->id,
            'group_name' => 'Performance',
            'spec_name' => 'GPU',
            'spec_value' => 'NVIDIA RTX 4080',
        ]);
        ProductSpecification::create([
            'product_id' => $bareProduct->id,
            'group_name' => 'Performance',
            'spec_name' => 'CPU',
            'spec_value' => 'Intel Core i9-13900HX',
        ]);

        $retailer = Retailer::create([
            'name' => 'Lenovo Store',
            'slug' => 'lenovo-store',
            'domain' => 'lenovo.com',
            'is_active' => true,
        ]);

        Offer::create([
            'product_id' => $bareProduct->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'title' => 'Legion 7i at Lenovo',
            'affiliate_url' => 'https://lenovo.com/legion-7i',
            'price' => 2499.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $bareProduct->update(['description' => 'Flagship Intel Core i9 gaming laptop with high-end RTX graphics.']);

        $fullScore = $qualityService->calculateQualityScore($bareProduct->fresh());
        // 50 + 15 (specs) + 15 (offer) + 5 (description) = 85 pts
        $this->assertEquals(85, $fullScore);
        $this->assertEquals('Good', $qualityService->getQualityGrade($fullScore));

        // 4. Verify SEO Eligibility Guard
        $seoService = app(SeoEligibilityService::class);
        $this->assertTrue($seoService->isIndexable($bareProduct->fresh(), $market));
        $this->assertEquals('index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1', $seoService->getRobotsDirective($bareProduct->fresh(), $market));
    }
}
