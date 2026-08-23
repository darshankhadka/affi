<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AffiliateClick;
use App\Models\AffiliateProvider;
use App\Models\AutomationJob;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Health\HealthCheckService;
use App\Services\Pricing\PriceFreshnessService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(
        protected PriceFreshnessService $freshnessService,
        protected HealthCheckService $healthService
    ) {
    }

    /**
     * Get real-time administrative dashboard overview
     */
    public function overview(): JsonResponse
    {
        $today = now()->startOfDay();

        $metrics = [
            'catalog' => [
                'total_products' => Product::count(),
                'published_products' => Product::where('status', 'published')->count(),
                'draft_products' => Product::where('status', 'draft')->count(),
                'total_categories' => Category::count(),
                'total_brands' => Brand::count(),
            ],
            'offers' => [
                'total_offers' => Offer::count(),
                'active_offers' => Offer::where('is_active', true)->count(),
                'in_stock_offers' => Offer::where('is_active', true)->where('availability', 'in_stock')->count(),
                'stale_offers' => $this->freshnessService->countStaleOffers(),
            ],
            'affiliates' => [
                'total_providers' => AffiliateProvider::count(),
                'connected_providers' => AffiliateProvider::where('status', 'connected')->count(),
                'total_retailers' => Retailer::count(),
                'active_retailers' => Retailer::where('is_active', true)->count(),
            ],
            'performance' => [
                'clicks_today' => AffiliateClick::where('clicked_at', '>=', $today)->count(),
                'clicks_total' => AffiliateClick::count(),
            ],
            'recent_automation_jobs' => AutomationJob::latest()->limit(5)->get(),
            'system_health' => $this->healthService->check(),
        ];

        return $this->success($metrics, 'Dashboard metrics retrieved.');
    }
}
