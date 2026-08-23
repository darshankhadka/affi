<?php

namespace App\Services\Health;

use App\Models\AffiliateProvider;
use App\Models\Offer;
use App\Services\Affiliate\AffiliateRegistry;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckService
{
    public function __construct(protected AffiliateRegistry $registry)
    {
    }

    /**
     * Run lightweight system health diagnostics suitable for shared hosting.
     */
    public function check(): array
    {
        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (Exception $e) {
            $dbConnected = false;
        }

        $cacheAccessible = false;
        try {
            $key = 'health:ping:' . time();
            Cache::put($key, 'pong', 5);
            $cacheAccessible = Cache::get($key) === 'pong';
            Cache::forget($key);
        } catch (Exception $e) {
            $cacheAccessible = false;
        }

        $staleOffersCount = 0;
        try {
            $staleOffersCount = Offer::where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('last_checked_at')
                          ->orWhere('next_check_at', '<=', now());
                })->count();
        } catch (Exception $e) {
            // ignore
        }

        $providersStatus = [];
        try {
            $providers = AffiliateProvider::all();
            foreach ($providers as $provider) {
                $status = 'disconnected';
                if ($this->registry->has($provider->code)) {
                    $connector = $this->registry->get($provider->code);
                    $status = $connector->isConnected($provider) ? 'connected' : 'disconnected';
                }
                $providersStatus[] = [
                    'code' => $provider->code,
                    'name' => $provider->name,
                    'status' => $status,
                ];
            }
        } catch (Exception $e) {
            // ignore
        }

        $overallStatus = ($dbConnected && $cacheAccessible) ? 'healthy' : 'critical';

        return [
            'status' => $overallStatus,
            'php_version' => PHP_VERSION,
            'database_connected' => $dbConnected,
            'cache_accessible' => $cacheAccessible,
            'stale_offers_count' => $staleOffersCount,
            'providers' => $providersStatus,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
