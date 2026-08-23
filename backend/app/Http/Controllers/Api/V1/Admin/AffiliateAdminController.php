<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\AffiliateProviderResource;
use App\Http\Resources\Api\V1\RetailerResource;
use App\Models\AffiliateProvider;
use App\Models\Retailer;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateAdminController extends BaseApiController
{
    public function __construct(protected AuditLoggerService $auditLogger)
    {
    }

    /**
     * List all affiliate providers
     */
    public function providers(): JsonResponse
    {
        $providers = AffiliateProvider::with(['accounts.market'])->get();
        return $this->success(AffiliateProviderResource::collection($providers));
    }

    /**
     * Update provider settings/credentials
     */
    public function updateProvider(Request $request, int $id): JsonResponse
    {
        $provider = AffiliateProvider::findOrFail($id);

        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'rate_limit_per_minute' => ['sometimes', 'integer', 'min:1', 'max:600'],
            'config' => ['nullable', 'array'],
        ]);

        $oldValues = $provider->toArray();
        $provider->update($validated);

        // Update status
        $hasConfig = !empty($provider->config['access_key']) || !empty($provider->config['api_key']);
        $provider->update(['status' => $hasConfig ? 'connected' : 'disconnected']);

        $this->auditLogger->log('provider.update', $provider, $oldValues, $provider->toArray());

        return $this->success(new AffiliateProviderResource($provider), 'Provider settings updated.');
    }

    /**
     * List retailers
     */
    public function retailers(Request $request): JsonResponse
    {
        $query = Retailer::with('affiliateProvider')->withCount('offers');

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%")->orWhere('domain', 'like', "%{$search}%");
        }

        $retailers = $query->orderBy('name')->get();
        return $this->success(RetailerResource::collection($retailers));
    }

    /**
     * Store new retailer
     */
    public function storeRetailer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'domain' => ['required', 'string', 'max:150'],
            'logo_url' => ['nullable', 'url'],
            'affiliate_provider_id' => ['nullable', 'exists:affiliate_providers,id'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        $retailer = Retailer::create(array_merge($validated, ['slug' => $slug]));

        $this->auditLogger->log('retailer.create', $retailer, null, $retailer->toArray());

        return $this->success(new RetailerResource($retailer), 'Retailer registered successfully.', 201);
    }
}
