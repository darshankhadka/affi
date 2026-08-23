<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $registry = app(AffiliateRegistry::class);
        $driver = $registry->has($this->code) ? $registry->get($this->code) : null;

        $hasCredentials = false;
        if (!empty($this->config)) {
            $hasCredentials = !empty($this->config['api_token']) 
                || !empty($this->config['access_key']) 
                || !empty($this->config['account_sid'])
                || !empty($this->config['api_key']);
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => (bool) $this->is_active,
            'rate_limit_per_minute' => (int) $this->rate_limit_per_minute,
            'status' => $this->status,
            'has_credentials' => $hasCredentials,
            'supported_markets' => $driver ? $driver->getSupportedMarkets() : ['us'],
            'supported_currencies' => $driver ? $driver->getSupportedCurrencies() : ['USD'],
            'supported_categories' => $driver ? $driver->getSupportedCategories() : [],
            'last_sync_at' => $this->last_sync_at?->toIso8601String(),
            'retailers_count' => $this->retailers()->count(),
            'offers_count' => $this->offers()->count(),
        ];
    }
}
