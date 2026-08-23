<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Pricing\BestPriceService;
use App\Services\Quality\DataQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaleOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_and_deactivated_offers_are_audited_and_excluded_from_best_price(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'us')->first();
        $brand = Brand::firstOrCreate(['slug' => 'razer'], ['name' => 'Razer', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Razer Viper V3 Pro',
            'slug' => 'razer-viper-v3-pro',
            'status' => 'published',
        ]);

        $retailer = Retailer::create([
            'name' => 'Razer Store',
            'slug' => 'razer-store',
            'domain' => 'razer.com',
            'is_active' => true,
        ]);

        // Stale offer (last checked 72 hours ago)
        $staleOffer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'RZ-VIPER-V3',
            'title' => 'Razer Viper V3 Pro',
            'affiliate_url' => 'https://razer.com/viper-v3',
            'price' => 159.99,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
            'last_checked_at' => now()->subHours(72),
        ]);

        $qualityService = app(DataQualityService::class);
        $audit = $qualityService->audit();

        $this->assertGreaterThanOrEqual(1, $audit['stale_offers_count']);

        // Now deactivate offer and recalculate best price
        $staleOffer->update(['is_active' => false]);

        $pricingService = app(BestPriceService::class);
        $pricingService->recalculate($product, $market);

        $bestPrice = $product->bestPrices()->where('market_id', $market->id)->first();
        // Zero active offers -> BestPrice record is purged
        $this->assertNull($bestPrice);
    }
}
