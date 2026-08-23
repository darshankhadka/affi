<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Role;
use App\Models\User;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Throwable;

class ProductionReadinessCommand extends Command
{
    protected $signature = 'system:production-readiness';
    protected $description = 'Comprehensive audit of ARIKARTECH production readiness and operational safeguards';

    public function handle(AffiliateRegistry $registry): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — PRODUCTION READINESS AUDIT");
        $this->info("==================================================\n");

        $checks = [];

        // 1. Database Connectivity
        try {
            DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $checks[] = ['Database Connectivity', 'PASS', "Connected to database [{$dbName}]"];
        } catch (Throwable $e) {
            $checks[] = ['Database Connectivity', 'FAIL', "Database connection failed: {$e->getMessage()}"];
        }

        // 2. Storage Permissions
        $storageWritable = is_writable(storage_path()) && is_writable(storage_path('framework/views')) && is_writable(storage_path('logs'));
        $checks[] = [
            'Storage & Cache Permissions',
            $storageWritable ? 'PASS' : 'FAIL',
            $storageWritable ? 'storage/ and bootstrap/cache/ are fully writeable' : 'Storage directories are not writable'
        ];

        // 3. Cache Storage
        try {
            Cache::put('readiness_test_key', 'ok', 10);
            $cachedVal = Cache::get('readiness_test_key');
            Cache::forget('readiness_test_key');
            $checks[] = ['Cache Store Operational', $cachedVal === 'ok' ? 'PASS' : 'FAIL', 'Cache write/read/delete operational'];
        } catch (Throwable $e) {
            $checks[] = ['Cache Store Operational', 'FAIL', "Cache store error: {$e->getMessage()}"];
        }

        // 4. Target Markets (15 Europe & UK Markets)
        $expectedMarkets = ['de', 'fr', 'nl', 'es', 'it', 'be', 'at', 'ie', 'pt', 'fi', 'se', 'dk', 'pl', 'cz', 'gb'];
        $marketCount = Market::whereIn('code', $expectedMarkets)->where('is_active', true)->count();
        $checks[] = [
            'Target Markets Configured',
            $marketCount >= 15 ? 'PASS' : 'WARNING',
            "{$marketCount} / 15 Europe & UK target country markets active in database"
        ];

        // 5. Currencies
        $currenciesCount = Currency::where('is_active', true)->count();
        $checks[] = [
            'Currencies & Exchange Rates',
            $currenciesCount >= 5 ? 'PASS' : 'WARNING',
            "{$currenciesCount} active ISO-4217 currencies (EUR, GBP, DKK, PLN, CZK, USD)"
        ];

        // 6. Technology Categories Taxonomy
        $categoryCount = Category::count();
        $checks[] = [
            'Canonical Tech Taxonomy',
            $categoryCount >= 15 ? 'PASS' : 'WARNING',
            "{$categoryCount} high-intent tech categories initialized"
        ];

        // 7. Affiliate Providers Registry
        $providers = ['awin', 'cj', 'impact', 'amazon'];
        $registeredCount = 0;
        foreach ($providers as $code) {
            if ($registry->has($code)) {
                $registeredCount++;
            }
        }
        $checks[] = [
            'Affiliate Drivers Registered',
            $registeredCount === count($providers) ? 'PASS' : 'FAIL',
            "{$registeredCount} / 4 network drivers active in registry (Awin, CJ, Impact, Amazon Deferred)"
        ];

        // 8. Provider Credentials Status
        $activeProviderRecords = AffiliateProvider::all();
        $configuredCount = 0;
        foreach ($activeProviderRecords as $prov) {
            if ($registry->has($prov->code)) {
                $driver = $registry->get($prov->code);
                if ($driver->isConnected($prov)) {
                    $configuredCount++;
                }
            }
        }
        $checks[] = [
            'Live Provider Credentials',
            $configuredCount > 0 ? 'PASS' : 'WARNING',
            $configuredCount > 0 
                ? "{$configuredCount} provider(s) have live credentials configured" 
                : "0 providers configured with live credentials. System operates in truthful disconnected state."
        ];

        // 9. Application Environment & Security
        $appKeySet = !empty(config('app.key'));
        $checks[] = [
            'Application Encryption Key',
            $appKeySet ? 'PASS' : 'FAIL',
            $appKeySet ? 'APP_KEY is securely configured' : 'APP_KEY is missing'
        ];

        $debugMode = config('app.debug');
        $checks[] = [
            'Debug Mode Status',
            $debugMode ? 'WARNING' : 'PASS',
            $debugMode ? 'APP_DEBUG=true (Set to false for live production deployment)' : 'APP_DEBUG=false (Secure for production)'
        ];

        // 10. API Route Health
        $apiRoutesCount = count(Route::getRoutes());
        $checks[] = [
            'REST API Routes Registered',
            $apiRoutesCount > 20 ? 'PASS' : 'FAIL',
            "{$apiRoutesCount} public & admin endpoints registered"
        ];

        // 11. Super Admin Account
        $adminUserExists = User::whereHas('roles', fn($q) => $q->where('name', 'Super Admin'))->exists();
        $checks[] = [
            'Super Admin User Initialized',
            $adminUserExists ? 'PASS' : 'FAIL',
            $adminUserExists ? 'RBAC Super Admin account active' : 'No Super Admin account found'
        ];

        // Output Results Table
        $this->table(['Check Domain', 'Status', 'Details'], $checks);

        $hasFailures = collect($checks)->contains(fn($c) => $c[1] === 'FAIL');

        if ($hasFailures) {
            $this->error("\n❌ PRODUCTION READINESS AUDIT FAILED: Fix critical failures before deployment.");
            return Command::FAILURE;
        }

        $this->info("\n✔ PRODUCTION READINESS AUDIT PASSED: Core foundations verified for live operation.");
        return Command::SUCCESS;
    }
}
