<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Retailer;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Console\Command;

class AffiliateHealthCheckCommand extends Command
{
    protected $signature = 'affiliate:health-check {--market= : Filter by market code}';
    protected $description = 'Perform a truthful health and connectivity audit of all global affiliate providers, markets, and retailers';

    public function handle(AffiliateRegistry $registry): int
    {
        $this->info('================================================================');
        $this->info('ARIKARTECH — GLOBAL AFFILIATE HEALTH & CONNECTIVITY AUDIT');
        $this->info('================================================================');

        // 1. Providers Overview
        $this->newLine();
        $this->info('--- AFFILIATE PROVIDERS ---');
        $providers = AffiliateProvider::all();
        $providerRows = [];

        foreach ($providers as $provider) {
            $instance = $registry->has($provider->code) ? $registry->get($provider->code) : null;
            $conn = $instance ? $instance->testConnection($provider) : ['status' => 'unregistered', 'message' => 'Not in registry'];

            $statusBadge = match ($conn['status']) {
                'connected' => '🟢 CONNECTED',
                'not_configured' => '⚪ NOT CONFIGURED',
                'invalid_credentials' => '🔴 INVALID CREDENTIALS',
                'rate_limited' => '🟡 RATE LIMITED',
                'deferred' => '🔵 DEFERRED',
                default => '🔴 ' . strtoupper($conn['status']),
            };

            $providerRows[] = [
                $provider->code,
                $provider->name,
                $provider->type,
                $statusBadge,
                $conn['message'] ?? '—',
                isset($conn['latency_ms']) && $conn['latency_ms'] !== null ? "{$conn['latency_ms']} ms" : '—',
            ];
        }

        $this->table(
            ['Code', 'Name', 'Type', 'Status', 'Diagnostics', 'Latency'],
            $providerRows
        );

        // 2. Markets & Retailers Summary
        $this->newLine();
        $this->info('--- GLOBAL 35-MARKET MATRIX SUMMARY ---');
        $marketCode = $this->option('market');
        $marketsQuery = Market::withCount(['offers', 'retailers'])->orderBy('display_order');
        if ($marketCode) {
            $marketsQuery->where('code', strtolower($marketCode));
        }
        $markets = $marketsQuery->get();

        $marketRows = [];
        foreach ($markets as $m) {
            $freshOffers = Offer::where('market_id', $m->id)
                ->where('is_active', true)
                ->where('last_checked_at', '>=', now()->subHours(6))
                ->count();
            
            $agingOffers = Offer::where('market_id', $m->id)
                ->where('is_active', true)
                ->whereBetween('last_checked_at', [now()->subHours(24), now()->subHours(6)])
                ->count();

            $staleOffers = Offer::where('market_id', $m->id)
                ->where('is_active', true)
                ->where('last_checked_at', '<', now()->subHours(24))
                ->count();

            $marketRows[] = [
                strtoupper($m->code),
                $m->name,
                $m->defaultCurrency?->code ?? '—',
                $m->retailers_count,
                $m->offers_count,
                "{$freshOffers} fresh / {$agingOffers} aging / {$staleOffers} stale",
                $m->is_active ? '✅ Active' : '⏸ Inactive',
            ];
        }

        $this->table(
            ['Market', 'Country', 'Currency', 'Retailers', 'Active Offers', 'Freshness Breakdown', 'Status'],
            $marketRows
        );

        // 3. Retailer Connection Counts
        $totalRetailers = Retailer::count();
        $configuredRetailers = Retailer::whereIn('status', ['connected', 'approved'])->count();
        $pendingRetailers = Retailer::where('status', 'not_configured')->count();
        $activeOffers = Offer::where('is_active', true)->count();

        $this->newLine();
        $this->info("Total Locked Retailers: {$totalRetailers} | Active Configured: {$configuredRetailers} | Pending Credentials: {$pendingRetailers}");
        $this->info("Authoritative Active Store Offers in Catalog: {$activeOffers}");
        $this->info('✔ HEALTH CHECK COMPLETE: Truthful audit executed.');

        return Command::SUCCESS;
    }
}
