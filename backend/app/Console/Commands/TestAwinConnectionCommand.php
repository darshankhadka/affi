<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Services\Affiliate\AwinProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAwinConnectionCommand extends Command
{
    protected $signature = 'affiliate:test-awin';
    protected $description = 'Test live connectivity to the Awin Publisher API';

    public function handle(AwinProvider $connector): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — AWIN LIVE CONNECTION TEST");
        $this->info("==================================================\n");

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider) {
            $this->error("Awin provider record not found in database.");
            return Command::FAILURE;
        }

        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.awin.api_token');
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        $this->line("Provider: <comment>{$provider->name}</comment>");
        $maskedPublisherId = !empty($publisherId) ? substr($publisherId, 0, 2) . '****' . substr($publisherId, -2) : 'NOT CONFIGURED';
        $this->line("Publisher ID: <comment>{$maskedPublisherId}</comment>");
        $this->line("Credentials: " . (!empty($apiToken) && !empty($publisherId) ? '<info>CONFIGURED</info>' : '<error>NOT CONFIGURED</error>'));

        if (!$connector->isConnected($provider)) {
            $this->error("\nRESULT: NOT CONFIGURED");
            return Command::FAILURE;
        }

        $startTime = microtime(true);
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
            ])->timeout(10)->get("https://api.awin.com/publishers/{$publisherId}/programmes", [
                'relationship' => 'joined',
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $status = $response->status();

            $this->line("\nAPI Request: " . ($response->successful() ? '<info>SUCCESS</info>' : '<error>FAILED</error>'));
            $this->line("HTTP Status: <comment>{$status}</comment>");
            $this->line("Latency: <comment>{$latency} ms</comment>");

            if ($response->successful()) {
                $programmes = $response->json();
                $count = is_array($programmes) ? count($programmes) : 0;
                $this->line("Joined Programmes: <info>{$count}</info>");
                $this->info("\nRESULT: CONNECTED");
                return Command::SUCCESS;
            }

            if ($status === 401 || $status === 403) {
                $this->error("\nRESULT: INVALID CREDENTIALS");
                return Command::FAILURE;
            }

            $this->error("\nRESULT: ERROR (HTTP {$status}) - " . substr($response->body(), 0, 200));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->line("\nLatency: <comment>{$latency} ms</comment>");
            $this->error("Connection Exception: " . $e->getMessage());
            $this->error("\nRESULT: CONNECTION FAILED");
            return Command::FAILURE;
        }
    }
}
