<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Services\Quality\DataQualityService;
use App\Services\SEO\SeoEligibilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogHealthCommand extends Command
{
    protected $signature = 'catalog:health';
    protected $description = 'Comprehensive health and integrity check of the ARIKARTECH catalog';

    public function handle(DataQualityService $qualityService, SeoEligibilityService $seoService): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — CATALOG HEALTH & INTEGRITY AUDIT");
        $this->info("==================================================\n");

        $totalProducts = Product::count();
        $publishedProducts = Product::where('status', 'published')->count();
        $totalOffers = Offer::count();
        $activeOffers = Offer::where('is_active', true)->count();
        $staleOffers = Offer::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhere('last_checked_at', '<', now()->subHours(48));
            })->count();

        // Calculate indexable products across markets
        $indexableCount = 0;
        $usMarket = Market::where('code', 'us')->first();
        foreach (Product::where('status', 'published')->get() as $p) {
            if ($seoService->isIndexable($p, $usMarket)) {
                $indexableCount++;
            }
        }

        $auditResults = $qualityService->audit();
        $connectedProviders = AffiliateProvider::where('status', 'connected')->count();
        $totalMarkets = Market::where('is_active', true)->count();

        $tableData = [
            ['Database Connectivity', 'PASS', 'Database is reachable and responding'],
            ['Affiliate Providers', "{$connectedProviders} CONNECTED", "{$connectedProviders} providers active and verified"],
            ['Active Markets', "{$totalMarkets} Active", "Target regional markets initialized"],
            ['Canonical Products', "{$totalProducts} Total", "{$publishedProducts} published, " . ($totalProducts - $publishedProducts) . " draft/review"],
            ['Store Offers', "{$activeOffers} Active", "{$totalOffers} total offers in database"],
            ['Indexable Products (US)', "{$indexableCount} Indexable", "Products meeting strict SEO eligibility criteria"],
            ['Stale Offers (>48h)', "{$staleOffers} Stale", $staleOffers === 0 ? 'All active offers are fresh' : 'Offers scheduled for refresh'],
            ['Conflicting Identifiers', $auditResults['conflicting_identifiers_count'] === 0 ? '0' : (string)$auditResults['conflicting_identifiers_count'], 'Conflicting EAN/UPC/ASIN assignments'],
            ['Catalog Quality Issues', (string)$auditResults['total_issues'], 'Quality audit anomalies identified'],
        ];

        $this->table(['Health Metric', 'Status / Count', 'Details'], $tableData);

        if ($auditResults['conflicting_identifiers_count'] > 0) {
            $this->error("\n❌ CATALOG INTEGRITY WARNING: Conflicting identifiers found.");
            return Command::FAILURE;
        }

        $this->info("\n✔ CATALOG HEALTH CHECK COMPLETE: Integrity within operational bounds.");
        return Command::SUCCESS;
    }
}
