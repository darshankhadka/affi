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
use App\Services\SEO\MetadataService;
use App\Services\SEO\SeoEligibilityService;
use App\Services\SEO\StructuredDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EuropeUkMarketTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifteen_europe_and_uk_markets_are_active(): void
    {
        $this->artisan('system:init-foundation');

        $activeMarkets = Market::where('is_active', true)->pluck('code')->toArray();
        $expected = ['de', 'fr', 'nl', 'es', 'it', 'be', 'at', 'ie', 'pt', 'fi', 'se', 'dk', 'pl', 'cz', 'gb'];

        $this->assertCount(15, $activeMarkets);
        foreach ($expected as $exp) {
            $this->assertContains($exp, $activeMarkets);
        }

        // Verify non-target markets are inactive
        $inactiveMarkets = Market::where('is_active', false)->pluck('code')->toArray();
        $this->assertContains('us', $inactiveMarkets);
        $this->assertContains('au', $inactiveMarkets);
        $this->assertContains('nz', $inactiveMarkets);
    }

    public function test_european_currencies_are_correctly_mapped(): void
    {
        $this->artisan('system:init-foundation');

        $expectedCurrencies = [
            'de' => 'EUR',
            'fr' => 'EUR',
            'nl' => 'EUR',
            'es' => 'EUR',
            'it' => 'EUR',
            'be' => 'EUR',
            'at' => 'EUR',
            'ie' => 'EUR',
            'pt' => 'EUR',
            'fi' => 'EUR',
            'se' => 'EUR',
            'dk' => 'DKK',
            'pl' => 'PLN',
            'cz' => 'CZK',
            'gb' => 'GBP',
        ];

        foreach ($expectedCurrencies as $marketCode => $currencyCode) {
            $m = Market::where('code', $marketCode)->with('defaultCurrency')->first();
            $this->assertNotNull($m, "Market '{$marketCode}' must exist");
            $this->assertNotNull($m->default_currency_id, "Market '{$marketCode}' default_currency_id must not be null");
            $this->assertNotNull($m->currency_id, "Market '{$marketCode}' currency_id accessor must not be null");
            $this->assertEquals($m->default_currency_id, $m->currency_id);
            $this->assertEquals($currencyCode, $m->defaultCurrency->code, "Market '{$marketCode}' currency mismatch");

            $matchingCurrency = Currency::where('code', $currencyCode)->first();
            $this->assertEquals($matchingCurrency->id, $m->currency_id, "Market '{$marketCode}' currency_id must match {$currencyCode} id");
        }
    }

    public function test_seo_metadata_generates_correct_hreflang_for_europe(): void
    {
        $this->artisan('system:init-foundation');

        $brand = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Apple MacBook Air M4',
            'slug' => 'apple-macbook-air-m4',
            'status' => 'published',
        ]);

        $de = Market::where('code', 'de')->first();
        $metadataService = app(MetadataService::class);
        $meta = $metadataService->getProductMetadata($product, $de);

        $this->assertEquals('https://arikartech.com/de/products/apple-macbook-air-m4', $meta['canonical']);
        $this->assertArrayHasKey('de-de', $meta['hreflang']);
        $this->assertArrayHasKey('fr-fr', $meta['hreflang']);
        $this->assertArrayHasKey('en-gb', $meta['hreflang']);
        $this->assertArrayHasKey('x-default', $meta['hreflang']);
        $this->assertEquals('https://arikartech.com/gb/products/apple-macbook-air-m4', $meta['hreflang']['x-default']);
    }

    public function test_multi_currency_best_price_isolation_in_europe(): void
    {
        $this->artisan('system:init-foundation');

        $de = Market::where('code', 'de')->first();
        $gb = Market::where('code', 'gb')->first();
        $eur = Currency::where('code', 'EUR')->first();
        $gbp = Currency::where('code', 'GBP')->first();

        $brand = Brand::firstOrCreate(['slug' => 'asus'], ['name' => 'ASUS', 'is_active' => true]);
        $category = Category::first();

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'ASUS ROG Zephyrus G16',
            'slug' => 'asus-rog-zephyrus-g16',
            'status' => 'published',
        ]);

        $retailerDE = Retailer::create(['name' => 'MediaMarkt DE', 'slug' => 'mediamarkt-de', 'domain' => 'mediamarkt.de', 'is_active' => true]);
        $retailerGB = Retailer::create(['name' => 'Currys GB', 'slug' => 'currys-gb', 'domain' => 'currys.co.uk', 'is_active' => true]);

        Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailerDE->id,
            'market_id' => $de->id,
            'currency_id' => $eur->id,
            'sku' => 'DE-ROG-16',
            'title' => 'ASUS ROG G16 DE Deal',
            'affiliate_url' => 'https://mediamarkt.de/rog16',
            'price' => 1999.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailerGB->id,
            'market_id' => $gb->id,
            'currency_id' => $gbp->id,
            'sku' => 'GB-ROG-16',
            'title' => 'ASUS ROG G16 GB Deal',
            'affiliate_url' => 'https://currys.co.uk/rog16',
            'price' => 1699.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $pricingService = app(BestPriceService::class);
        $pricingService->recalculate($product, $de);
        $pricingService->recalculate($product, $gb);

        $deBest = $product->bestPrices()->where('market_id', $de->id)->first();
        $this->assertEquals(1999.00, $deBest->min_price);
        $this->assertEquals($eur->id, $deBest->currency_id);

        $gbBest = $product->bestPrices()->where('market_id', $gb->id)->first();
        $this->assertEquals(1699.00, $gbBest->min_price);
        $this->assertEquals($gbp->id, $gbBest->currency_id);
    }
}
