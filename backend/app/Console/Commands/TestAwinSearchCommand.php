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

        $this->line("Target Market: <comment>{$market->name} ({$market->code})</comment>");
        $this->line("Keywords: <comment>{$keywords}</comment>");
        $this->line("Requested Limit: <comment>{$limit}</comment>");

        $endpoint = "https://api.awin.com/publishers/{$publisherId}/productsearch";
        $params = [
            'query' => $keywords,
            'limit' => $limit,
            'language' => $market->code === 'gb' || $market->code === 'uk' ? 'en' : $market->code,
        ];

        $this->line("Request Endpoint: <comment>GET {$endpoint}</comment>");
        $this->line("Parameters: <comment>" . json_encode($params) . "</comment>");

        $startTime = microtime(true);
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
            ])->timeout(12)->get($endpoint, $params);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $status = $response->status();
            $contentType = $response->header('Content-Type');

            $this->line("\nHTTP Status: <comment>{$status}</comment>");
            $this->line("Content-Type: <comment>{$contentType}</comment>");
            $this->line("Latency: <comment>{$latency} ms</comment>");

            if (!$response->successful()) {
                $bodyPreview = substr($response->body(), 0, 500);
                $this->error("\nHTTP Error ({$status}): {$bodyPreview}");
                return Command::FAILURE;
            }

            $raw = $response->json();
            $this->line("Response JSON Keys: <comment>" . implode(', ', array_keys(is_array($raw) ? $raw : [])) . "</comment>");

            $items = $raw['products'] ?? $raw['data'] ?? (is_array($raw) && isset($raw[0]) ? $raw : []);
            $this->info("Raw Products Found: " . count($items));

            $normalizedCount = 0;
            foreach ($items as $idx => $item) {
                $dto = $connector->normalizeAwinItem($item, $market);
                if ($dto) {
                    $normalizedCount++;
                    if ($normalizedCount <= 3) {
                        $this->line("\n--- Product #" . ($idx + 1) . " ---");
                        $this->line("Name: <info>{$dto->name}</info>");
                        $this->line("Brand: <comment>{$dto->brandName}</comment>");
                        $this->line("Price: <info>" . ($dto->offers[0]->price ?? 'N/A') . " " . ($dto->offers[0]->currencyCode ?? '') . "</info>");
                        $this->line("Retailer: <comment>" . ($dto->offers[0]->retailerName ?? 'N/A') . " (" . ($dto->offers[0]->retailerDomain ?? '') . ")</comment>");
                        $this->line("Affiliate URL: <comment>" . substr($dto->offers[0]->affiliateUrl ?? '', 0, 80) . "...</comment>");
                    }
                }
            }

            $this->info("\nSuccessfully normalized {$normalizedCount} / " . count($items) . " products.");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $this->line("\nLatency: <comment>{$latency} ms</comment>");
            $this->error("Search Exception: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
