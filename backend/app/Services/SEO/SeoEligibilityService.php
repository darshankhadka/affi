<?php

namespace App\Services\SEO;

use App\Models\Market;
use App\Models\Product;
use App\Services\Quality\DataQualityService;

class SeoEligibilityService
{
    protected DataQualityService $qualityService;

    public function __construct(?DataQualityService $qualityService = null)
    {
        $this->qualityService = $qualityService ?? app(DataQualityService::class);
    }

    /**
     * Determine if a canonical product is eligible for search engine indexation in a market
     */
    public function isIndexable(Product $product, ?Market $market = null): bool
    {
        if ($product->status !== 'published') {
            return false;
        }

        if (empty($product->slug) || empty($product->name)) {
            return false;
        }

        // Must pass baseline quality threshold (>= 40 points)
        if ($this->qualityService->calculateQualityScore($product) < 40) {
            return false;
        }

        // Must have at least one active offer in target market (or globally if no market specified)
        $query = $product->offers()->where('is_active', true);
        if ($market) {
            $query->where('market_id', $market->id);
        }

        return $query->exists();
    }

    /**
     * Get robots meta directive for a product page in a market
     */
    public function getRobotsDirective(Product $product, Market $market): string
    {
        if ($this->isIndexable($product, $market)) {
            return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        }

        return 'noindex, follow';
    }
}
