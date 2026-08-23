<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Pricing\BestPriceService;
use App\Services\SEO\SeoEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiMarketIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_markets_maintain_isolated_best_prices_and_currencies(): void
    {
        $this->artisan('system:init-foundation');

        $usMarket = Market::where('code', 'us')->first();
        $ukMarket = Market::where('code', 'uk')->first();
        $deMarket = Market::where('code', 'de')->first();

        $usd = Currency::where('code', 'USD')->first();
        $gbp = Currency::where('code', 'GBP')->first();

        $brand = Brand::firstOrCreate(['slug' => 'apple'], ['name' => 'Apple', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Apple MacBook Pro 16 M3',
            'slug' => 'apple-macbook-pro-16-m3',
            'status' => 'published',
        ]);

        $usRetailer = Retailer::create([
            'name' => 'Best Buy US',
            'slug' => 'best-buy-us',
            'domain' => 'bestbuy.com',
            'is_active' => true,
        ]);

        $ukRetailer = Retailer::create([
            'name' => 'Currys UK',
            'slug' => 'currys-uk',
            'domain' => 'currys.co.uk',
            'is_active' => true,
        ]);

        // US Offer ($2499.00 USD)
        Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $usRetailer->id,
            'market_id' => $usMarket->id,
            'currency_id' => $usd->id,
            'sku' => 'US-MBP-16',
            'title' => 'MacBook Pro 16 US Deal',
            'affiliate_url' => 'https://bestbuy.com/mbp16',
            'price' => 2499.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        // UK Offer (£2199.00 GBP)
        Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $ukRetailer->id,
            'market_id' => $ukMarket->id,
            'currency_id' => $gbp->id,
            'sku' => 'UK-MBP-16',
            'title' => 'MacBook Pro 16 UK Deal',
            'affiliate_url' => 'https://currys.co.uk/mbp16',
            'price' => 2199.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $pricingService = app(BestPriceService::class);
        $pricingService->recalculate($product, $usMarket);
        $pricingService->recalculate($product, $ukMarket);

        // Verify US best price
        $usBest = $product->bestPrices()->where('market_id', $usMarket->id)->first();
        $this->assertNotNull($usBest);
        $this->assertEquals(2499.00, $usBest->min_price);
        $this->assertEquals($usd->id, $usBest->currency_id);

        // Verify UK best price
        $ukBest = $product->bestPrices()->where('market_id', $ukMarket->id)->first();
        $this->assertNotNull($ukBest);
        $this->assertEquals(2199.00, $ukBest->min_price);
        $this->assertEquals($gbp->id, $ukBest->currency_id);

        // Verify DE Market (No offers in DE -> Not indexable, 0 best prices)
        $seoService = app(SeoEligibilityService::class);
        $this->assertFalse($seoService->isIndexable($product, $deMarket));
        $this->assertEquals('noindex, follow', $seoService->getRobotsDirective($product, $deMarket));
    }
}
