<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'rate_to_usd' => (float) $this->rate_to_usd,
            'decimals' => $this->decimals,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
