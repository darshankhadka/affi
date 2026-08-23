<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Services\Affiliate\AwinProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAwinSearchCommand extends Command
{
    protected $signature = 'affiliate:test-awin-search 
        {--market=gb : Target market code (gb, de, fr, etc.)}
        {--keywords=laptop : Search query or keywords}
        {--limit=5 : Result limit}';

    protected $description = 'Perform a live Awin product search with diagnostics';

    public function handle(AwinProvider $connector): int
    {
        $marketCode = (string) $this->option('market');
        $keywords = (string) $this->option('keywords');
        $limit = min((int) $this->option('limit'), 50);

        $this->info("==================================================");
        $this->info("ARIKARTECH — AWIN LIVE PRODUCT SEARCH DIAGNOSTIC");
        $this->info("==================================================\n");

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$connector->isConnected($provider)) {
            $this->error("Awin provider is not configured.");
            return Command::FAILURE;
        }

        $market = Market::where('code', strtolower($marketCode))->first();
        if (!$market) {
            $this->error("Market '{$marketCode}' not found.");
            return Command::FAILURE;
        }

        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.awin.api_token');
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        $currencyCode = $market->defaultCurrency?->code ?? $market->currency?->code ?? 'EUR';

        $this->line("Market: <comment>{$market->name} ({$market->code})</comment>");
        $this->line("Currency: <comment>{$currencyCode}</comment>");
        $this->line("Keywords: <comment>{$keywords}</comment>");
        $this->line("Limit: <comment>{$limit}</comment>");

        $endpoint = "https://api.awin.com/publishers/{$publisherId}/productsearch";
        $params = [
            'query' => $keywords,
            'limit' => $limit,
            'language' => in_array($market->code, ['gb', 'uk', 'ie']) ? 'en' : $market->code,
        ];

        $this->line("Endpoint: <comment>GET {$endpoint}</comment>");

        $startTime = microtime(true);
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
            ])->timeout(12)->get($endpoint, $params);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $status = $response->status();
            $contentType = $response->header('Content-Type');

            $this->line("HTTP Status: <comment>{$status}</comment>");
            $this->line("Response type: <comment>{$contentType}</comment>");
            $this->line("Latency: <comment>{$latency} ms</comment>");

            if ($response->successful()) {
                $raw = $response->json();
                $items = $raw['products'] ?? $raw['data'] ?? (is_array($raw) && isset($raw[0]) ? $raw : []);
                $this->info("Products returned: " . count($items));

                $normalizedCount = 0;
                foreach ($items as $idx => $item) {
                    $dto = $connector->normalizeAwinItem($item, $market);
                    if ($dto) {
                        $normalizedCount++;
                        if ($normalizedCount <= 3) {
                            $this->line("\n--- Product #" . ($idx + 1) . " ---");
                            $this->line("Name: <info>{$dto->name}</info>");
                            $this->line("Brand: <comment>{$dto->brandName}</comment>");
                            $this->line("Price: <info>" . ($dto->offer->price ?? 'N/A') . " " . ($dto->offer->currencyCode ?? '') . "</info>");
                            $this->line("Retailer: <comment>" . ($dto->offer->retailerName ?? 'N/A') . " (" . ($dto->offer->retailerDomain ?? '') . ")</comment>");
                            $this->line("Affiliate URL: <comment>" . substr($dto->offer->affiliateUrl ?? '', 0, 80) . "...</comment>");
                        }
                    }
                }

                $this->info("Products normalized: {$normalizedCount}");
                $this->info("\nRESULT: CONNECTED / SUCCESS");
                return Command::SUCCESS;
            }

            if ($status === 401) {
                $this->error("\nRESULT: INVALID CREDENTIALS");
                $this->line("<comment>Authentication failed (401 Unauthorized).</comment>");
                return Command::FAILURE;
            }

            if ($status === 403) {
                $this->error("\nRESULT: FORBIDDEN / ENDPOINT NOT PERMITTED");
                $this->line("<comment>The credentials are configured and authenticated, but this specific product search API endpoint is not permitted under the current Awin publisher account policy/scopes.</comment>");
                $this->line("<comment>API Response: " . trim($response->body()) . "</comment>");
                return Command::FAILURE;
            }

            if ($status === 404) {
                $this->error("\nRESULT: ENDPOINT NOT FOUND (HTTP 404)");
                return Command::FAILURE;
            }

            if ($status === 429) {
                $this->error("\nRESULT: RATE LIMITED (HTTP 429)");
                return Command::FAILURE;
            }

            if ($status >= 500) {
                $this->error("\nRESULT: PROVIDER SERVER ERROR (HTTP {$status})");
                return Command::FAILURE;
            }

            $this->error("\nRESULT: REQUEST ERROR (HTTP {$status}) - " . substr($response->body(), 0, 200));
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->line("\nLatency: <comment>{$latency} ms</comment>");
            $this->error("Search Exception: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
