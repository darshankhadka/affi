<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'rate_limit_per_minute' => (int) $this->rate_limit_per_minute,
            'status' => $this->status,
            'last_sync_at' => $this->last_sync_at?->toIso8601String(),
        ];
    }
}
