<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Console\Command;

class AffiliateTestProviderCommand extends Command
{
    protected $signature = 'affiliate:test-provider {provider : Provider code (e.g. awin, amazon, cj, impact, tradedoubler, rakuten, partnerize, direct)}';
    protected $description = 'Test real connection and credential diagnostics for an affiliate provider';

    public function handle(AffiliateRegistry $registry): int
    {
        $code = strtolower($this->argument('provider'));

        if (!$registry->has($code)) {
            $this->error("Unknown affiliate provider [{$code}]. Registered: " . implode(', ', array_keys($registry->all())));
            return Command::FAILURE;
        }

        $providerModel = AffiliateProvider::firstOrCreate(['code' => $code], [
            'name' => ucfirst($code),
            'type' => 'api',
            'is_active' => false,
            'status' => 'not_configured',
        ]);

        $instance = $registry->get($code);
        $this->info("Testing connection for provider: {$instance->getName()} ({$code})...");

        $result = $instance->testConnection($providerModel);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Provider Code', $code],
                ['Provider Name', $instance->getName()],
                ['Connected', $result['connected'] ? 'YES' : 'NO'],
                ['Status', strtoupper($result['status'])],
                ['Message', $result['message'] ?? 'None'],
                ['Latency', isset($result['latency_ms']) && $result['latency_ms'] !== null ? "{$result['latency_ms']} ms" : 'N/A'],
                ['Rate Limit', "{$instance->getRateLimit()} req/min"],
                ['Supported Markets', implode(', ', array_slice($instance->getSupportedMarkets(), 0, 10)) . (count($instance->getSupportedMarkets()) > 10 ? '...' : '')],
                ['Supported Currencies', implode(', ', $instance->getSupportedCurrencies())],
                ['Supports Feed', $instance->supportsProductFeed() ? 'YES' : 'NO'],
                ['Supports API', $instance->supportsApi() ? 'YES' : 'NO'],
                ['Supports Deep Links', $instance->supportsDeepLinks() ? 'YES' : 'NO'],
            ]
        );

        if ($result['connected']) {
            $this->info("✔ Connection verified successfully for [{$code}].");
            return Command::SUCCESS;
        }

        $this->warn("⚠ Provider [{$code}] status is [{$result['status']}]: {$result['message']}");
        return Command::SUCCESS;
    }
}
