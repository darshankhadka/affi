<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AffiliateClick;
use App\Models\SearchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsAdminController extends BaseApiController
{
    /**
     * Search & discovery intelligence
     */
    public function searchIntelligence(Request $request): JsonResponse
    {
        // Top user queries
        $topQueries = SearchLog::select('query', DB::raw('COUNT(*) as count'), DB::raw('MAX(results_count) as max_results'), DB::raw('MAX(created_at) as last_searched_at'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        // Zero-result queries with opportunity score
        $zeroResultQueries = SearchLog::where('results_count', 0)
            ->select('query', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as last_searched_at'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $recencyBoost = 10;
                $opportunityScore = ($item->count * 15) + $recencyBoost;
                return [
                    'query' => $item->query,
                    'count' => (int) $item->count,
                    'opportunity_score' => $opportunityScore,
                    'last_searched_at' => $item->last_searched_at,
                ];
            });

        // Searches by market
        $searchesByMarket = SearchLog::select('market_id', DB::raw('COUNT(*) as count'))
            ->with('market')
            ->groupBy('market_id')
            ->get()
            ->map(fn($item) => [
                'market' => $item->market?->code ?? 'unknown',
                'count' => (int) $item->count,
            ]);

        return $this->success([
            'total_searches' => SearchLog::count(),
            'zero_result_searches_count' => SearchLog::where('results_count', 0)->count(),
            'top_queries' => $topQueries,
            'zero_result_queries' => $zeroResultQueries,
            'searches_by_market' => $searchesByMarket,
        ]);
    }

    /**
     * Conversion funnel overview
     */
    public function conversionOverview(Request $request): JsonResponse
    {
        $totalClicks = AffiliateClick::count();
        $clicksToday = AffiliateClick::whereDate('clicked_at', today())->count();
        $totalSearches = SearchLog::count();

        // Clicks by provider
        $clicksByProvider = DB::table('affiliate_clicks')
            ->join('retailers', 'affiliate_clicks.retailer_id', '=', 'retailers.id')
            ->leftJoin('affiliate_providers', 'retailers.affiliate_provider_id', '=', 'affiliate_providers.id')
            ->select(DB::raw('COALESCE(affiliate_providers.name, "Direct Retailer") as provider_name'), DB::raw('COUNT(*) as click_count'))
            ->groupBy('provider_name')
            ->orderByDesc('click_count')
            ->get();

        // Clicks by retailer
        $clicksByRetailer = DB::table('affiliate_clicks')
            ->join('retailers', 'affiliate_clicks.retailer_id', '=', 'retailers.id')
            ->select('retailers.name as retailer_name', DB::raw('COUNT(*) as click_count'))
            ->groupBy('retailers.name')
            ->orderByDesc('click_count')
            ->limit(10)
            ->get();

        // Clicks by market
        $clicksByMarket = DB::table('affiliate_clicks')
            ->join('markets', 'affiliate_clicks.market_id', '=', 'markets.id')
            ->select('markets.code as market_code', DB::raw('COUNT(*) as click_count'))
            ->groupBy('markets.code')
            ->orderByDesc('click_count')
            ->get();

        // Clicks by top products
        $clicksByProduct = DB::table('affiliate_clicks')
            ->join('products', 'affiliate_clicks.product_id', '=', 'products.id')
            ->select('products.id', 'products.name as product_name', 'products.slug', DB::raw('COUNT(*) as click_count'))
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc('click_count')
            ->limit(10)
            ->get();

        // CTR calculation (Clicks / Total interactions)
        $totalInteractions = $totalSearches + $totalClicks;
        $ctr = $totalInteractions > 0 ? round(($totalClicks / $totalInteractions) * 100, 2) : 0.0;

        return $this->success([
            'metrics' => [
                'total_clicks' => $totalClicks,
                'clicks_today' => $clicksToday,
                'total_searches' => $totalSearches,
                'ctr_percentage' => $ctr,
            ],
            'clicks_by_provider' => $clicksByProvider,
            'clicks_by_retailer' => $clicksByRetailer,
            'clicks_by_market' => $clicksByMarket,
            'clicks_by_product' => $clicksByProduct,
        ]);
    }
}
