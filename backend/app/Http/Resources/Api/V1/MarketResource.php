<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'locale' => $this->locale,
            'hreflang' => $this->hreflang,
            'is_active' => (bool) $this->is_active,
            'default_currency' => new CurrencyResource($this->whenLoaded('defaultCurrency')),
        ];
    }
}
