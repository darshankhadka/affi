<?php

namespace App\Console\Commands;

use App\Models\AffiliateClick;
use App\Models\SearchLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogConversionStatsCommand extends Command
{
    protected $signature = 'catalog:conversion-stats';
    protected $description = 'Report conversion funnel metrics, network attribution, and outbound clicks';

    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — CONVERSION FUNNEL & REVENUE STATS");
        $this->info("==================================================\n");

        $totalClicks = AffiliateClick::count();
        $clicksToday = AffiliateClick::whereDate('clicked_at', today())->count();
        $totalSearches = SearchLog::count();
        $totalInteractions = $totalSearches + $totalClicks;
        $ctr = $totalInteractions > 0 ? round(($totalClicks / $totalInteractions) * 100, 2) : 0.0;

        $funnelRows = [
            ['Catalog User Searches', $totalSearches, '100% Top of Funnel'],
            ['Outbound Affiliate Referrals', $totalClicks, "{$ctr}% Referral CTR"],
            ['Referrals Today', $clicksToday, 'Current Day Traffic'],
        ];

        $this->table(['Funnel Stage', 'Count', 'Conversion Metric'], $funnelRows);

        // Clicks by provider
        $clicksByProvider = DB::table('affiliate_clicks')
            ->join('retailers', 'affiliate_clicks.retailer_id', '=', 'retailers.id')
            ->leftJoin('affiliate_providers', 'retailers.affiliate_provider_id', '=', 'affiliate_providers.id')
            ->select(DB::raw('COALESCE(affiliate_providers.name, "Direct Retailer") as provider_name'), DB::raw('COUNT(*) as click_count'))
            ->groupBy('provider_name')
            ->orderByDesc('click_count')
            ->get();

        if ($clicksByProvider->isNotEmpty()) {
            $this->info("\nReferrals by Affiliate Provider:");
            $provRows = $clicksByProvider->map(fn($p) => [$p->provider_name, $p->click_count])->toArray();
            $this->table(['Provider', 'Referrals'], $provRows);
        }

        return Command::SUCCESS;
    }
}
