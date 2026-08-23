<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\OfferResource;
use App\Http\Resources\Api\V1\PriceHistoryResource;
use App\Models\Offer;
use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferController extends BaseApiController
{
    /**
     * List all offers for a product
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $marketId = $request->input('market_id');

        $query = Offer::where('product_id', $product->id)
            ->where('is_active', true)
            ->with(['retailer', 'currency', 'market']);

        if ($marketId) {
            $query->where('market_id', $marketId);
        }

        $offers = $query->orderBy('price', 'asc')->get();
        return $this->success(OfferResource::collection($offers));
    }

    /**
     * Get price history data points for an offer or product
     */
    public function priceHistory(Request $request, int $productId): JsonResponse
    {
        $marketId = $request->input('market_id');
        $days = min((int) $request->input('days', 90), 365);

        $query = PriceHistory::where('product_id', $productId)
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at', 'asc');

        if ($marketId) {
            $query->where('market_id', $marketId);
        }

        $history = $query->get();
        return $this->success(PriceHistoryResource::collection($history));
    }
}
