<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\MarketResource;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends BaseApiController
{
    /**
     * List all available markets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Market::with('defaultCurrency')->orderBy('display_order');
        
        // Non-admin requests only get active markets
        if (!$request->user()?->hasRole(['Super Admin', 'Admin'])) {
            $query->where('is_active', true);
        }

        $markets = $query->get();
        return $this->success(MarketResource::collection($markets));
    }

    /**
     * Get market details by code
     */
    public function show(string $code): JsonResponse
    {
        $market = Market::with('defaultCurrency')
            ->where('code', strtolower($code))
            ->first();

        if (!$market) {
            return $this->error('Market not found.', 404);
        }

        return $this->success(new MarketResource($market));
    }
}
