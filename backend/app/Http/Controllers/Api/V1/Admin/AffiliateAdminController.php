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
     * List retailers with advanced filters
     */
    public function retailers(Request $request): JsonResponse
    {
        $query = Retailer::with('affiliateProvider')->withCount('offers');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($market = $request->input('market')) {
            $query->where('market_code', strtolower($market));
        }

        if ($country = $request->input('country')) {
            $query->where('country', strtoupper($country));
        }

        if ($providerId = $request->input('provider_id')) {
            $query->where('affiliate_provider_id', $providerId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($network = $request->input('network')) {
            $query->where('affiliate_network', $network);
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
            'country' => ['nullable', 'string', 'size:2'],
            'market_code' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'logo_url' => ['nullable', 'url'],
            'website_url' => ['nullable', 'url'],
            'affiliate_provider_id' => ['nullable', 'exists:affiliate_providers,id'],
            'affiliate_network' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($validated['name']);
        $retailer = Retailer::create(array_merge($validated, ['slug' => $slug, 'code' => $slug]));

        $this->auditLogger->log('retailer.create', $retailer, null, $retailer->toArray());

        return $this->success(new RetailerResource($retailer), 'Retailer registered successfully.', 201);
    }

    /**
     * Update retailer settings
     */
    public function updateRetailer(Request $request, int $id): JsonResponse
    {
        $retailer = Retailer::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'domain' => ['sometimes', 'string', 'max:150'],
            'country' => ['nullable', 'string', 'size:2'],
            'market_code' => ['nullable', 'string', 'max:10'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'logo_url' => ['nullable', 'url'],
            'website_url' => ['nullable', 'url'],
            'affiliate_provider_id' => ['nullable', 'exists:affiliate_providers,id'],
            'affiliate_network' => ['nullable', 'string'],
            'status' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $oldValues = $retailer->toArray();
        $retailer->update($validated);

        $this->auditLogger->log('retailer.update', $retailer, $oldValues, $retailer->toArray());

        return $this->success(new RetailerResource($retailer), 'Retailer updated successfully.');
    }

    /**
     * Test single retailer configuration
     */
    public function testRetailer(int $id, \App\Services\Affiliate\AffiliateRegistry $registry): JsonResponse
    {
        $retailer = Retailer::with(['affiliateProvider', 'market'])->findOrFail($id);

        $provider = $retailer->affiliateProvider;
        $providerInstance = $provider && $registry->has($provider->code) ? $registry->get($provider->code) : null;

        $providerConn = $provider && $providerInstance ? $providerInstance->testConnection($provider) : [
            'connected' => false,
            'status' => 'not_configured',
            'message' => 'No active provider driver attached.',
        ];

        return $this->success([
            'retailer' => new RetailerResource($retailer),
            'provider_connection' => $providerConn,
        ], 'Retailer test executed.');
    }

    /**
     * Test live API connection for an affiliate provider
     */
    public function testProviderConnection(int $id, \App\Services\Affiliate\AffiliateRegistry $registry): JsonResponse
    {
        $provider = AffiliateProvider::findOrFail($id);

        if (!$registry->has($provider->code)) {
            return $this->error("Provider driver for '{$provider->code}' is not loaded.", 400);
        }

        $connector = $registry->get($provider->code);
        $result = $connector->testConnection($provider);

        // Update provider status based on actual connection test
        $newStatus = $result['connected'] ? 'connected' : ($result['status'] === 'not_configured' ? 'disconnected' : 'error');
        $provider->update(['status' => $newStatus]);

        return $this->success($result, 'Provider connection test executed.');
    }

    /**
     * Trigger a bounded sync for a provider in a given market
     */
    public function triggerSync(Request $request, int $id, \App\Services\Affiliate\AffiliateRegistry $registry): JsonResponse
    {
        $provider = AffiliateProvider::findOrFail($id);

        $marketCode = $request->input('market', 'us');
        $limit = min((int) $request->input('limit', 25), 50);
        $keywords = $request->input('keywords', 'Laptops');

        if (!$registry->has($provider->code)) {
            return $this->error("Provider driver for '{$provider->code}' is not loaded.", 400);
        }

        $exitCode = \Illuminate\Support\Facades\Artisan::call('automation:ingest-provider', [
            '--provider' => $provider->code,
            '--market' => $marketCode,
            '--limit' => $limit,
            '--keywords' => $keywords,
        ]);

        $output = \Illuminate\Support\Facades\Artisan::output();

        $provider->update(['last_sync_at' => now()]);

        return $this->success([
            'exit_code' => $exitCode,
            'output' => trim($output),
        ], 'Provider sync batch completed.');
    }
}
