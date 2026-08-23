<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'retailer_id' => $this->retailer_id,
            'market_id' => $this->market_id,
            'currency_id' => $this->currency_id,
            'sku' => $this->sku,
            'title' => $this->title,
            'affiliate_url' => "/api/v1/affiliates/out/{$this->id}", // Encapsulated outbound affiliate click endpoint
            'price' => (float) $this->price,
            'original_price' => $this->original_price ? (float) $this->original_price : null,
            'discount_percentage' => $this->discount_percentage ? (float) $this->discount_percentage : null,
            'shipping_cost' => $this->shipping_cost ? (float) $this->shipping_cost : null,
            'availability' => $this->availability,
            'condition' => $this->condition,
            'is_active' => (bool) $this->is_active,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'retailer' => new RetailerResource($this->whenLoaded('retailer')),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'market' => new MarketResource($this->whenLoaded('market')),
        ];
    }
}
