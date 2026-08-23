<?php

namespace App\Services\Pricing;

use App\Models\BestPrice;
use App\Models\Market;
use App\Models\Offer;
use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BestPriceService
{
    /**
     * Recompute and materialize best prices for a product in a given market (or all active markets).
     */
    public function recalculate(Product $product, ?Market $market = null): void
    {
        $marketIds = $market ? [$market->id] : Market::where('is_active', true)->pluck('id')->toArray();

        foreach ($marketIds as $marketId) {
            $offers = Offer::where('product_id', $product->id)
                ->where('market_id', $marketId)
                ->where('is_active', true)
                ->get();

            if ($offers->isEmpty()) {
                BestPrice::where('product_id', $product->id)
                    ->where('market_id', $marketId)
                    ->delete();
                continue;
            }

            $inStockOffers = $offers->where('availability', 'in_stock');
            $bestOffer = $inStockOffers->sortBy('price')->first() ?? $offers->sortBy('price')->first();

            $minPrice = $offers->min('price');
            $maxPrice = $offers->max('price');
            $currencyId = $bestOffer->currency_id;

            BestPrice::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'market_id' => $marketId,
                ],
                [
                    'currency_id' => $currencyId,
                    'min_price' => $minPrice,
                    'max_price' => $maxPrice,
                    'best_offer_id' => $bestOffer->id,
                    'offer_count' => $offers->count(),
                    'in_stock_offer_count' => $inStockOffers->count(),
                ]
            );
        }
    }

    /**
     * Record price history snapshot for an offer if price or availability changed.
     */
    public function recordPriceHistory(Offer $offer): ?PriceHistory
    {
        $latest = PriceHistory::where('offer_id', $offer->id)
            ->latest('recorded_at')
            ->first();

        // Only log if no previous record or price/availability changed
        if (!$latest || abs($latest->price - $offer->price) > 0.001 || $latest->availability !== $offer->availability) {
            return PriceHistory::create([
                'offer_id' => $offer->id,
                'product_id' => $offer->product_id,
                'market_id' => $offer->market_id,
                'currency_id' => $offer->currency_id,
                'price' => $offer->price,
                'original_price' => $offer->original_price,
                'availability' => $offer->availability,
                'recorded_at' => now(),
            ]);
        }

        return null;
    }
}
