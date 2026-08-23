<?php

namespace App\Services\Quality;

use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductIdentifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DataQualityService
{
    /**
     * Run full data quality assessment and return metric anomalies
     */
    public function audit(): array
    {
        $issues = [];

        // 1. Check for conflicting identifiers (same type & normalized value pointing to different products)
        $conflicts = DB::table('product_identifiers')
            ->select('type', 'normalized_value', DB::raw('COUNT(DISTINCT product_id) as prod_count'))
            ->groupBy('type', 'normalized_value')
            ->having('prod_count', '>', 1)
            ->get();

        foreach ($conflicts as $c) {
            $issues[] = [
                'type' => 'conflicting_identifier',
                'severity' => 'critical',
                'message' => "Conflicting identifier {$c->type}:{$c->normalized_value} is assigned to multiple canonical products.",
                'entity_id' => 0,
            ];
        }

        // 2. Check for published products missing images
        $productsWithoutImages = Product::where('status', 'published')
            ->whereNull('primary_image_id')
            ->whereDoesntHave('images')
            ->limit(50)
            ->get();

        foreach ($productsWithoutImages as $p) {
            $issues[] = [
                'type' => 'missing_image',
                'severity' => 'warning',
                'message' => "Product '{$p->name}' (ID: {$p->id}) has no primary or gallery images.",
                'entity_id' => $p->id,
            ];
        }

        // 3. Stale offers (not checked in over 48 hours)
        $staleOffers = Offer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', now()->subHours(48));
            })
            ->limit(50)
            ->get();

        foreach ($staleOffers as $off) {
            $issues[] = [
                'type' => 'stale_offer',
                'severity' => 'medium',
                'message' => "Offer ID {$off->id} ({$off->title}) has not been refreshed in >48 hours.",
                'entity_id' => $off->id,
            ];
        }

        return [
            'total_issues' => count($issues),
            'conflicting_identifiers_count' => $conflicts->count(),
            'products_missing_image_count' => $productsWithoutImages->count(),
            'products_missing_specs_count' => Product::doesntHave('specifications')->count(),
            'stale_offers_count' => $staleOffers->count(),
            'suspicious_price_drops_count' => 0,
            'issues' => array_slice($issues, 0, 50),
        ];
    }

    /**
     * Calculate deterministic product quality score (0 to 100)
     */
    public function calculateQualityScore(Product $product): int
    {
        $score = 0;

        // 1. Name & Slug Completeness (10 pts)
        if (!empty($product->name) && strlen(trim($product->name)) >= 5 && !empty($product->slug)) {
            $score += 10;
        }

        // 2. Brand Assigned (10 pts)
        if ($product->brand_id && $product->brand) {
            $score += 10;
        }

        // 3. Model Number Assigned (10 pts)
        if (!empty($product->model_number)) {
            $score += 10;
        }

        // 4. Canonical Identifiers Present (20 pts)
        $identifiersCount = $product->identifiers()->count();
        if ($identifiersCount >= 1) {
            $score += 20;
        }

        // 5. Images Available (15 pts)
        if ($product->primary_image_id || $product->images()->exists()) {
            $score += 15;
        }

        // 6. Specifications Detailed (15 pts)
        $specsCount = $product->specifications()->count();
        if ($specsCount >= 2) {
            $score += 15;
        } elseif ($specsCount === 1) {
            $score += 8;
        }

        // 7. Active Retailer Offers Present (15 pts)
        $activeOffersCount = $product->offers()->where('is_active', true)->count();
        if ($activeOffersCount >= 1) {
            $score += 15;
        }

        // 8. Description Content (5 pts)
        if (!empty($product->description) && strlen(trim($product->description)) >= 20) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Map quality score to deterministic rating grade
     */
    public function getQualityGrade(int $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Needs Improvement';
        return 'Not Publishable';
    }

    /**
     * Validate an offer before saving/updating to prevent corrupt pricing
     */
    public function validateOfferData(array $offerData): array
    {
        $errors = [];

        if (isset($offerData['price']) && (float) $offerData['price'] <= 0) {
            $errors[] = 'Price must be greater than zero.';
        }

        if (isset($offerData['affiliate_url']) && !filter_var($offerData['affiliate_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Affiliate URL format is invalid.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
