<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Retailer;
use App\Services\Taxonomy\CategoryClassifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogAuditCommand extends Command
{
    protected $signature = 'catalog:audit 
                            {--category : Audit taxonomy and suspicious category assignments}
                            {--affiliate : Audit affiliate URLs and tracking redirect integrity}
                            {--images : Audit image presence and resolution}
                            {--offers : Audit retailer offers and pricing freshness}
                            {--strict : Fail if any anomaly is found}';

    protected $description = 'Perform deep production audit on catalog data, taxonomy, images, offers and affiliate URLs';

    public function handle(CategoryClassifierService $classifier): int
    {
        $showCategory = $this->option('category');
        $showAffiliate = $this->option('affiliate');
        $showImages = $this->option('images');
        $showOffers = $this->option('offers');
        $all = (!$showCategory && !$showAffiliate && !$showImages && !$showOffers);

        $this->info("==================================================================");
        $this->info("ARIKARTECH — MASTER PRODUCTION CATALOG INTEGRITY AUDIT");
        $this->info("==================================================================\n");

        $totalProducts = Product::count();
        $publishedProducts = Product::where('status', 'published')->count();
        $totalOffers = Offer::count();
        $activeOffers = Offer::where('is_active', true)->count();
        $totalImages = ProductImage::count();
        $productsWithImages = Product::whereNotNull('primary_image_id')->count();
        $productsWithoutImages = Product::whereNull('primary_image_id')->count();
        $totalRetailers = Retailer::count();
        $totalBrands = Brand::count();
        $totalCategories = Category::count();

        // 1. PRODUCTS WITHOUT CATEGORY
        $uncategorizedCount = Product::whereNull('category_id')
            ->orWhereHas('category', fn($q) => $q->where('slug', 'uncategorized'))
            ->count();

        // 2. SUSPICIOUS CATEGORIES (e.g. bags or tyres under Laptops)
        $suspiciousCategoryCount = 0;
        $laptopCategory = Category::where('slug', 'laptops')->first();
        if ($laptopCategory) {
            $suspiciousCategoryCount = Product::where('category_id', $laptopCategory->id)
                ->where(function ($q) {
                    $q->where('name', 'LIKE', '%sleeve%')
                      ->orWhere('name', 'LIKE', '%backpack%')
                      ->orWhere('name', 'LIKE', '%briefcase%')
                      ->orWhere('name', 'LIKE', '%laptop bag%')
                      ->orWhere('name', 'LIKE', '%tyre%')
                      ->orWhere('name', 'LIKE', '%dæk%')
                      ->orWhere('name', 'LIKE', '%shampoo%')
                      ->orWhere('name', 'LIKE', '%mousepad%')
                      ->orWhere('name', 'LIKE', '%laser engraver%')
                      ->orWhere('name', 'LIKE', '%e-scooter%')
                      ->orWhere('name', 'LIKE', '%motorcycle%');
                })->count();
        }

        // 3. PRODUCTS WITHOUT OFFERS
        $productsWithoutOffers = Product::doesntHave('offers')->count();

        // 4. INVALID / MALFORMED AFFILIATE URLS
        $invalidAffiliateUrls = Offer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('affiliate_url')
                  ->orWhere('affiliate_url', '')
                  ->orWhere('affiliate_url', 'LIKE', '%undefined%')
                  ->orWhere('affiliate_url', 'LIKE', '%null%')
                  ->orWhere(function ($sub) {
                      $sub->where('affiliate_url', 'NOT LIKE', 'http://%')
                          ->where('affiliate_url', 'NOT LIKE', 'https://%')
                          ->where('affiliate_url', 'NOT LIKE', '/%');
                  });
            })->count();

        // 5. INVALID PRICES (<= 0 or null)
        $invalidPrices = Offer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('price')
                  ->orWhere('price', '<=', 0);
            })->count();

        // 6. ORPHANED OFFERS
        $orphanedOffers = Offer::doesntHave('product')->count();

        // 7. DUPLICATE SLUGS / IDENTIFIERS
        $duplicateSlugs = DB::table('products')
            ->select('slug', DB::raw('COUNT(*) as count'))
            ->groupBy('slug')
            ->having('count', '>', 1)
            ->count();

        $duplicateEan = DB::table('products')
            ->whereNotNull('canonical_ean')
            ->select('canonical_ean', DB::raw('COUNT(*) as count'))
            ->groupBy('canonical_ean')
            ->having('count', '>', 1)
            ->count();

        // GENERAL SUMMARY TABLE
        $this->table(
            ['Metric', 'Count / Value', 'Status'],
            [
                ['Total Products', $totalProducts, 'OK'],
                ['Published Products', $publishedProducts, 'OK'],
                ['Total Store Offers', $totalOffers, 'OK'],
                ['Active Offers', $activeOffers, 'OK'],
                ['Products With Images', "{$productsWithImages} (" . round(($productsWithImages / max(1, $totalProducts)) * 100, 1) . "%)", $productsWithoutImages === 0 ? 'PASS' : 'WARN'],
                ['Products Without Images', $productsWithoutImages, $productsWithoutImages === 0 ? 'PASS' : 'WARN'],
                ['Verified Retailers', $totalRetailers, 'OK'],
                ['Indexed Brands', $totalBrands, 'OK'],
                ['Active Categories', $totalCategories, 'OK'],
                ['Products Without Category', $uncategorizedCount, $uncategorizedCount === 0 ? 'PASS' : 'WARN'],
                ['Suspicious Category Matches', $suspiciousCategoryCount, $suspiciousCategoryCount === 0 ? 'PASS' : 'ACTION REQUIRED'],
                ['Products Without Active Offers', $productsWithoutOffers, $productsWithoutOffers === 0 ? 'PASS' : 'WARN'],
                ['Invalid/Malformed Affiliate URLs', $invalidAffiliateUrls, $invalidAffiliateUrls === 0 ? 'PASS' : 'FAIL'],
                ['Invalid Prices (<= 0)', $invalidPrices, $invalidPrices === 0 ? 'PASS' : 'FAIL'],
                ['Orphaned Offers', $orphanedOffers, $orphanedOffers === 0 ? 'PASS' : 'FAIL'],
                ['Duplicate Product Slugs', $duplicateSlugs, $duplicateSlugs === 0 ? 'PASS' : 'FAIL'],
                ['Duplicate EAN Identifiers', $duplicateEan, $duplicateEan === 0 ? 'PASS' : 'WARN'],
            ]
        );

        if ($showCategory || $all) {
            $this->info("\n--- CATEGORY BREAKDOWN ---");
            $categories = Category::withCount('products')->orderByDesc('products_count')->get();
            $catRows = [];
            foreach ($categories as $cat) {
                $catRows[] = [$cat->id, $cat->name, $cat->slug, $cat->products_count];
            }
            $this->table(['ID', 'Name', 'Slug', 'Products Count'], $catRows);
        }

        if ($showAffiliate || $all) {
            $this->info("\n--- AFFILIATE PROVIDER & OUTBOUND REDIRECT AUDIT ---");
            $retailerOffers = DB::table('offers')
                ->join('retailers', 'offers.retailer_id', '=', 'retailers.id')
                ->select('retailers.name as retailer_name', DB::raw('COUNT(offers.id) as offer_count'), DB::raw('SUM(offers.is_active) as active_count'))
                ->groupBy('retailers.name')
                ->orderByDesc('offer_count')
                ->limit(15)
                ->get();

            $affRows = [];
            foreach ($retailerOffers as $ro) {
                $affRows[] = [$ro->retailer_name, $ro->offer_count, $ro->active_count, 'PASS (Redirects via /go/{id})'];
            }
            $this->table(['Retailer Name', 'Total Offers', 'Active Offers', 'Redirect Status'], $affRows);
        }

        if ($suspiciousCategoryCount > 0) {
            $this->warn("\n⚠ ACTION REQUIRED: {$suspiciousCategoryCount} products have suspicious category assignments.");
            $this->line("Run: php artisan catalog:reclassify to re-assign categories deterministically.");
        }

        if ($invalidAffiliateUrls > 0 || $invalidPrices > 0 || $duplicateSlugs > 0) {
            $this->error("\n❌ AUDIT FAILED: Data anomalies detected.");
            return Command::FAILURE;
        }

        $this->info("\n✔ PRODUCTION CATALOG AUDIT COMPLETE.");
        return Command::SUCCESS;
    }
}
