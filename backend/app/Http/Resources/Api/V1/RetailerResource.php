<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetailerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'logo_url' => $this->logo_url,
            'is_active' => (bool) $this->is_active,
            'affiliate_provider' => new AffiliateProviderResource($this->whenLoaded('affiliateProvider')),
            'offers_count' => $this->whenCounted('offers'),
        ];
    }
}
