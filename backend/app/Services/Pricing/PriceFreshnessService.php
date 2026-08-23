<?php

namespace App\Services\Pricing;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Collection;

class PriceFreshnessService
{
    /**
     * Get offers that need price verification/refreshing, bounded by limit.
     * Prioritizes active offers that haven't been checked longest or where next_check_at <= now().
     *
     * @param int $limit
     * @return Collection<int, Offer>
     */
    public function getStaleOffers(int $limit = 50): Collection
    {
        return Offer::with(['product', 'retailer.affiliateProvider', 'market', 'currency'])
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('last_checked_at')
                      ->orWhere('next_check_at', '<=', now())
                      ->orWhereNull('next_check_at');
            })
            ->orderByRaw('CASE WHEN last_checked_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('next_check_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Count stale offers across the entire system.
     */
    public function countStaleOffers(): int
    {
        return Offer::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('last_checked_at')
                      ->orWhere('next_check_at', '<=', now())
                      ->orWhereNull('next_check_at');
            })
            ->count();
    }
}
