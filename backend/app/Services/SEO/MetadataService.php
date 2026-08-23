<?php

namespace App\Services\SEO;

use App\Models\Category;
use App\Models\Market;
use App\Models\Product;

class MetadataService
{
    public function __construct(protected ?SeoEligibilityService $eligibilityService = null)
    {
        $this->eligibilityService = $eligibilityService ?? new SeoEligibilityService();
    }

    /**
     * Generate metadata payload for a Product page in a given Market
     */
    public function getProductMetadata(Product $product, Market $market): array
    {
        $siteUrl = config('app.url', 'https://arikartech.com');
        $canonicalUrl = "{$siteUrl}/{$market->code}/products/{$product->slug}";
        $brandName = $product->brand?->name ?? 'Tech';
        
        $bestPrice = $product->bestPrices()->where('market_id', $market->id)->first();
        $priceText = '';
        if ($bestPrice && $bestPrice->min_price > 0) {
            $currencySymbol = $bestPrice->currency?->symbol ?? '$';
            $priceText = " from {$currencySymbol}" . number_format($bestPrice->min_price, 2);
        }

        $title = "{$product->name} Best Price & Deals{$priceText} | ARIKARTECH";
        $description = $product->short_description 
            ? "Compare prices for {$product->name} across verified retailers. {$product->short_description}"
            : "Find the best deals and compare prices for {$product->name} by {$brandName} across top authorized retailers on ARIKARTECH.";

        $activeMarkets = Market::where('is_active', true)->get();
        $hreflang = [];
        foreach ($activeMarkets as $m) {
            $hreflang[$m->hreflang] = "{$siteUrl}/{$m->code}/products/{$product->slug}";
        }

        $robots = $this->eligibilityService->getRobotsDirective($product, $market);

        return [
            'title' => $title,
            'description' => substr($description, 0, 160),
            'canonical' => $canonicalUrl,
            'robots' => $robots,
            'hreflang' => $hreflang,
            'open_graph' => [
                'title' => $title,
                'description' => substr($description, 0, 200),
                'url' => $canonicalUrl,
                'site_name' => 'ARIKARTECH',
                'locale' => $market->locale,
                'type' => 'product',
                'image' => $product->primaryImage?->url,
            ],
        ];
    }

    /**
     * Generate metadata for Category page
     */
    public function getCategoryMetadata(Category $category, Market $market): array
    {
        $siteUrl = config('app.url', 'https://arikartech.com');
        $canonicalUrl = "{$siteUrl}/{$market->code}/categories/{$category->slug}";

        $title = "Best {$category->name} Deals & Price Comparison ({$market->name}) | ARIKARTECH";
        $description = $category->description 
            ? "Discover and compare top {$category->name}. {$category->description}"
            : "Compare prices and discover top-rated {$category->name} from trusted tech retailers in {$market->name} on ARIKARTECH.";

        return [
            'title' => $title,
            'description' => substr($description, 0, 160),
            'canonical' => $canonicalUrl,
            'robots' => 'index, follow',
        ];
    }
}
