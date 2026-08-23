<?php

namespace App\Console\Commands;

use App\Models\AffiliateClick;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Models\SearchLog;
use Illuminate\Console\Command;

class CatalogStatsCommand extends Command
{
    protected $signature = 'catalog:stats';
    protected $description = 'Display real statistics for the ARIKARTECH catalog and monetization engine';

    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — CATALOG & MONETIZATION STATISTICS");
        $this->info("==================================================\n");

        $stats = [
            ['Canonical Products', Product::count()],
            ['Published Products', Product::where('status', 'published')->count()],
            ['Active Store Offers', Offer::where('is_active', true)->count()],
            ['Verified Retailers', Retailer::count()],
            ['Active Brands', Brand::count()],
            ['Catalog Categories', Category::count()],
            ['Active Country Markets', Market::where('is_active', true)->count()],
            ['Logged Search Queries', SearchLog::count()],
            ['Zero-Result Searches', SearchLog::where('results_count', 0)->count()],
            ['Affiliate Referrals (All-Time)', AffiliateClick::count()],
            ['Affiliate Referrals (Today)', AffiliateClick::whereDate('clicked_at', today())->count()],
        ];

        $this->table(['Metric', 'Count'], $stats);

        return Command::SUCCESS;
    }
}
