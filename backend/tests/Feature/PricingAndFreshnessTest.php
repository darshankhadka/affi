<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Pricing\BestPriceService;
use App\Services\Pricing\PriceFreshnessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingAndFreshnessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_best_price_service_computes_min_price_and_prioritizes_in_stock(): void
    {
        $brand = Brand::create(['name' => 'NVIDIA', 'slug' => 'nvidia']);
        $cat = Category::first();
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'GeForce RTX 4080 Super',
            'slug' => 'geforce-rtx-4080-super',
            'status' => 'published',
        ]);

        $market = Market::where('code', 'us')->first();
        $currency = Currency::where('code', 'USD')->first();

        $retailer1 = Retailer::create(['name' => 'Retailer A', 'slug' => 'retailer-a', 'domain' => 'retailera.com']);
        $retailer2 = Retailer::create(['name' => 'Retailer B', 'slug' => 'retailer-b', 'domain' => 'retailerb.com']);

        // Offer 1: $999 (Out of stock)
        $offer1 = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer1->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'RTX 4080 Super A',
            'affiliate_url' => 'https://retailera.com/deal',
            'price' => 999.00,
            'availability' => 'out_of_stock',
            'is_active' => true,
        ]);

        // Offer 2: $1049 (In stock)
        $offer2 = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer2->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'RTX 4080 Super B',
            'affiliate_url' => 'https://retailerb.com/deal',
            'price' => 1049.00,
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $bestPriceService = app(BestPriceService::class);
        $bestPriceService->recalculate($product, $market);

        $bestPrice = $product->bestPrices()->where('market_id', $market->id)->first();
        $this->assertNotNull($bestPrice);
        $this->assertEquals(999.00, $bestPrice->min_price);
        $this->assertEquals(1049.00, $bestPrice->max_price);
        $this->assertEquals(2, $bestPrice->offer_count);
        $this->assertEquals(1, $bestPrice->in_stock_offer_count);
        // Best offer should prioritize in-stock
        $this->assertEquals($offer2->id, $bestPrice->best_offer_id);
    }

    public function test_price_history_records_snapshots_only_on_actual_change(): void
    {
        $brand = Brand::create(['name' => 'Intel', 'slug' => 'intel']);
        $cat = Category::first();
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $cat->id,
            'name' => 'Intel Core i9 14900K',
            'slug' => 'intel-core-i9-14900k',
            'status' => 'published',
        ]);

        $market = Market::where('code', 'us')->first();
        $currency = Currency::where('code', 'USD')->first();
        $retailer = Retailer::create(['name' => 'Best Buy', 'slug' => 'best-buy', 'domain' => 'bestbuy.com']);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $currency->id,
            'title' => 'Intel Core i9 14900K',
            'affiliate_url' => 'https://bestbuy.com/deal',
            'price' => 549.00,
            'availability' => 'in_stock',
            'is_active' => true,
        ]);

        $bestPriceService = app(BestPriceService::class);
        
        // Initial log
        $bestPriceService->recordPriceHistory($offer);
        $this->assertEquals(1, PriceHistory::where('offer_id', $offer->id)->count());

        // Same price again -> Should NOT duplicate
        $bestPriceService->recordPriceHistory($offer);
        $this->assertEquals(1, PriceHistory::where('offer_id', $offer->id)->count());

        // Price changes to $499
        $offer->price = 499.00;
        $bestPriceService->recordPriceHistory($offer);
        $this->assertEquals(2, PriceHistory::where('offer_id', $offer->id)->count());
    }
}
