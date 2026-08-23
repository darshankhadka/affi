<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Pricing\BestPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestPriceMultiCurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_best_price_prioritizes_in_stock_over_cheaper_out_of_stock_offer(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'us')->first();
        $brand = Brand::firstOrCreate(['slug' => 'asus'], ['name' => 'Asus', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'ASUS ROG Zephyrus G14',
            'slug' => 'asus-rog-zephyrus-g14',
            'status' => 'published',
        ]);

        $retailer1 = Retailer::create([
            'name' => 'Best Buy',
            'slug' => 'best-buy',
            'domain' => 'bestbuy.com',
            'is_active' => true,
        ]);

        $retailer2 = Retailer::create([
            'name' => 'B&H Photo',
            'slug' => 'bh-photo',
            'domain' => 'bhphotovideo.com',
            'is_active' => true,
        ]);

        // Offer 1: Out of stock at $1399
        $oosOffer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer1->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'BB-ROG-14',
            'title' => 'ASUS ROG 14 OOS',
            'affiliate_url' => 'https://bestbuy.com/rog14',
            'price' => 1399.00,
            'availability' => 'out_of_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        // Offer 2: In stock at $1499
        $inStockOffer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer2->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'BH-ROG-14',
            'title' => 'ASUS ROG 14 In Stock',
            'affiliate_url' => 'https://bhphotovideo.com/rog14',
            'price' => 1499.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $pricingService = app(BestPriceService::class);
        $pricingService->recalculate($product, $market);

        $bestPrice = $product->bestPrices()->where('market_id', $market->id)->first();
        $this->assertNotNull($bestPrice);

        // best_offer_id prioritizes in-stock offer ($1499)
        $this->assertEquals($inStockOffer->id, $bestPrice->best_offer_id);
        $this->assertEquals(1, $bestPrice->in_stock_offer_count);
        $this->assertEquals(2, $bestPrice->offer_count);
        $this->assertEquals(1399.00, $bestPrice->min_price);
        $this->assertEquals(1499.00, $bestPrice->max_price);
    }
}
