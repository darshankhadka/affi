<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BestPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'market_id' => $this->market_id,
            'currency_id' => $this->currency_id,
            'min_price' => (float) $this->min_price,
            'max_price' => (float) $this->max_price,
            'best_offer_id' => $this->best_offer_id,
            'offer_count' => (int) $this->offer_count,
            'in_stock_offer_count' => (int) $this->in_stock_offer_count,
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'best_offer' => new OfferResource($this->whenLoaded('bestOffer')),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
