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
            'code' => $this->code,
            'domain' => $this->domain,
            'country' => $this->country,
            'market_code' => $this->market_code,
            'currency_code' => $this->currency_code,
            'logo_url' => $this->logo_url,
            'website_url' => $this->website_url,
            'terms_url' => $this->terms_url,
            'affiliate_provider_id' => $this->affiliate_provider_id,
            'affiliate_network' => $this->affiliate_network,
            'affiliate_program_id' => $this->affiliate_program_id,
            'status' => $this->status ?? 'not_configured',
            'integration_type' => $this->integration_type ?? 'affiliate_network',
            'api_available' => (bool) $this->api_available,
            'feed_available' => (bool) $this->feed_available,
            'deep_link_supported' => (bool) $this->deep_link_supported,
            'price_tracking_supported' => (bool) $this->price_tracking_supported,
            'is_active' => (bool) $this->is_active,
            'last_successful_sync_at' => $this->last_successful_sync_at?->toIso8601String(),
            'last_failed_sync_at' => $this->last_failed_sync_at?->toIso8601String(),
            'last_error' => $this->last_error,
            'affiliate_provider' => new AffiliateProviderResource($this->whenLoaded('affiliateProvider')),
            'offers_count' => $this->whenCounted('offers'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
