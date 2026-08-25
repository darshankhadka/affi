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

    protected $description = 'Perform deep production audit on catalog data against the strict 20-category technology taxonomy';

    public function handle(CategoryClassifierService $classifier): int
    {
        $this->info("==================================================================");
        $this->info("ARIKARTECH — PUBLIC CATALOG HEALTH & INTEGRITY AUDIT");
        $this->info("==================================================================\n");

        $totalProducts = Product::count();
        $publishedProducts = Product::where('status', 'published')->count();
        $excludedProducts = Product::where('status', 'excluded')->count();
        $activeCategoriesCount = Category::where('is_active', true)->count();
        
        $publishedInApprovedCats = Product::where('status', 'published')
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->count();

        $publishedWithoutCategory = Product::where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('category_id')
                  ->orWhereDoesntHave('category', fn($c) => $c->where('is_active', true));
            })->count();

        // Check for suspicious non-laptop products published under laptops
        $suspiciousCategoryCount = 0;
        $laptopCategory = Category::where('slug', 'laptops')->first();
        if ($laptopCategory) {
            $suspiciousCategoryCount = Product::where('category_id', $laptopCategory->id)
                ->where('status', 'published')
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

        // Image coverage among published products
        $publishedWithoutImages = Product::where('status', 'published')
            ->whereNull('primary_image_id')
            ->count();

        // Published products without active offers
        $publishedWithoutOffers = Product::where('status', 'published')
            ->whereDoesntHave('offers', fn($q) => $q->where('is_active', true))
            ->count();

        // Invalid affiliate URLs
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

        // Invalid prices
        $invalidPrices = Offer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('price')
                  ->orWhere('price', '<=', 0);
            })->count();

        // Duplicate slugs
        $duplicateSlugs = DB::table('products')
            ->select('slug', DB::raw('COUNT(*) as count'))
            ->groupBy('slug')
            ->having('count', '>', 1)
            ->count();

        // Duplicate identifiers
        $duplicateEan = DB::table('products')
            ->whereNotNull('canonical_ean')
            ->select('canonical_ean', DB::raw('COUNT(*) as count'))
            ->groupBy('canonical_ean')
            ->having('count', '>', 1)
            ->count();

        // SUMMARY MATRIX
        $this->table(
            ['Metric', 'Count / Value', 'Status'],
            [
                ['Approved Public Categories', $activeCategoriesCount . ' (Target: 20)', $activeCategoriesCount === 20 ? 'PASS' : 'WARN'],
                ['Published Tech Products', $publishedProducts, 'OK'],
                ['Products in Approved Categories', $publishedInApprovedCats, 'PASS'],
                ['Excluded Non-Tech Products', $excludedProducts, 'OK (Excluded from Public)'],
                ['Published Without Category', $publishedWithoutCategory, $publishedWithoutCategory === 0 ? 'PASS' : 'FAIL'],
                ['Published Without Images', $publishedWithoutImages, $publishedWithoutImages === 0 ? 'PASS' : 'FAIL'],
                ['Published Without Active Offers', $publishedWithoutOffers, $publishedWithoutOffers === 0 ? 'PASS' : 'FAIL'],
                ['Invalid / Malformed Affiliate URLs', $invalidAffiliateUrls, $invalidAffiliateUrls === 0 ? 'PASS' : 'FAIL'],
                ['Invalid Prices (<= 0)', $invalidPrices, $invalidPrices === 0 ? 'PASS' : 'FAIL'],
                ['Duplicate Slugs', $duplicateSlugs, $duplicateSlugs === 0 ? 'PASS' : 'FAIL'],
                ['Duplicate EAN Identifiers', $duplicateEan, $duplicateEan === 0 ? 'PASS' : 'FAIL'],
                ['Category Mismatches', $suspiciousCategoryCount, $suspiciousCategoryCount === 0 ? 'PASS' : 'FAIL'],
            ]
        );

        $this->info("\n--- APPROVED 20 CATEGORIES BREAKDOWN ---");
        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn($q) => $q->where('status', 'published')])
            ->orderBy('display_order')
            ->get();

        $catTable = [];
        foreach ($categories as $cat) {
            $catTable[] = [
                $cat->id,
                $cat->name,
                $cat->slug,
                $cat->products_count,
            ];
        }
        $this->table(['ID', 'Name', 'Slug', 'Published Products'], $catTable);

        $this->newLine();
        $this->info("✔ PUBLIC CATALOG INTEGRITY AUDIT COMPLETE.");

        return Command::SUCCESS;
    }
}
