<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProvider;
use App\Models\Market;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AwinDatafeedService
{
    /**
     * Standard Awin Product Datafeed column list
     */
    public const DEFAULT_FEED_COLUMNS = [
        'aw_product_id',
        'product_name',
        'description',
        'search_price',
        'merchant_image_url',
        'aw_deep_link',
        'merchant_deep_link',
        'ean',
        'upc',
        'mpn',
        'brand_name',
        'merchant_name',
        'merchant_id',
        'category_name',
        'in_stock',
        'delivery_cost',
        'currency',
    ];

    /**
     * Fetch joined advertiser programmes from Awin Publisher API
     *
     * @return array<int, array{
     *   id: int,
     *   name: string,
     *   displayUrl: string,
     *   clickThroughUrl: string,
     *   currencyCode: string,
     *   primaryRegion: array{countryCode: string, name: string},
     *   primarySector: string,
     *   validDomains: array,
     *   status: string,
     *   linkStatus: string
     * }>
     */
    public function getJoinedProgrammes(AffiliateProvider $provider): array
    {
        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.awin.api_token');
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        if (empty($apiToken) || empty($publisherId)) {
            throw new RuntimeException("Awin credentials (API Token / Publisher ID) are not configured.");
        }

        $endpoint = "https://api.awin.com/publishers/{$publisherId}/programmes";
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
            ])->timeout(10)->get($endpoint, ['relationship' => 'joined']);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $status = $response->status();

            if ($status === 401) {
                throw new RuntimeException("Awin API authentication failed (401 Unauthorized). Please check API token.");
            }

            if ($status === 403) {
                throw new RuntimeException("Awin API 403 Forbidden: Programme list endpoint not permitted by account policy.");
            }

            if (!$response->successful()) {
                throw new RuntimeException("Awin API HTTP Error {$status} [{$latency}ms]: " . substr($response->body(), 0, 200));
            }

            $raw = $response->json();
            if (!is_array($raw)) {
                return [];
            }

            $programmes = [];
            foreach ($raw as $item) {
                if (isset($item['id'], $item['name'])) {
                    $programmes[] = [
                        'id' => (int) $item['id'],
                        'name' => (string) $item['name'],
                        'displayUrl' => (string) ($item['displayUrl'] ?? ''),
                        'clickThroughUrl' => (string) ($item['clickThroughUrl'] ?? ''),
                        'currencyCode' => (string) ($item['currencyCode'] ?? 'EUR'),
                        'primaryRegion' => [
                            'countryCode' => (string) ($item['primaryRegion']['countryCode'] ?? 'DE'),
                            'name' => (string) ($item['primaryRegion']['name'] ?? 'Germany'),
                        ],
                        'primarySector' => (string) ($item['primarySector'] ?? 'General'),
                        'validDomains' => (array) ($item['validDomains'] ?? []),
                        'status' => (string) ($item['status'] ?? 'Active'),
                        'linkStatus' => (string) ($item['linkStatus'] ?? 'online'),
                    ];
                }
            }

            return $programmes;
        } catch (RuntimeException $re) {
            throw $re;
        } catch (Throwable $e) {
            throw new RuntimeException("Awin connection error: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Build the standard Awin Product Datafeed download URL for an advertiser
     */
    public function getFeedUrl(
        int|string $advertiserId,
        Market $market,
        ?string $apiKey = null,
        bool $gzip = true
    ): string {
        $key = $apiKey ?: config('services.awin.datafeed_api_key', config('services.awin.api_token'));
        $lang = in_array($market->code, ['gb', 'uk', 'ie']) ? 'en' : $market->code;
        $cols = implode(',', self::DEFAULT_FEED_COLUMNS);
        $compression = $gzip ? 'compression/gzip/' : '';

        return "https://productdata.awin.com/datafeed/download/apikey/{$key}/language/{$lang}/mid/{$advertiserId}/columns/{$cols}/format/csv/delimiter/%2C/{$compression}";
    }

    /**
     * Download and extract feed content with memory limits and streaming safety
     *
     * @return array{
     *   success: bool,
     *   content: ?string,
     *   http_status: int,
     *   content_type: ?string,
     *   is_gzipped: bool,
     *   latency_ms: int,
     *   error: ?string
     * }
     */
    public function downloadFeed(string $feedUrl, int $timeoutSeconds = 30): array
    {
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
            ])->timeout($timeoutSeconds)->get($feedUrl);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $status = $response->status();
            $contentType = $response->header('Content-Type');

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'content' => null,
                    'http_status' => $status,
                    'content_type' => $contentType,
                    'is_gzipped' => false,
                    'latency_ms' => $latency,
                    'error' => "HTTP {$status}: " . substr($response->body(), 0, 300),
                ];
            }

            $rawBody = $response->body();
            $isGzipped = false;

            // Check if GZIP magic header (0x1f, 0x8b)
            if (strlen($rawBody) >= 2 && substr($rawBody, 0, 2) === "\x1f\x8b") {
                $isGzipped = true;
                $decoded = @gzdecode($rawBody);
                if ($decoded !== false) {
                    $rawBody = $decoded;
                } else {
                    return [
                        'success' => false,
                        'content' => null,
                        'http_status' => $status,
                        'content_type' => $contentType,
                        'is_gzipped' => true,
                        'latency_ms' => $latency,
                        'error' => "Failed to decompress GZIP feed content.",
                    ];
                }
            }

            return [
                'success' => true,
                'content' => $rawBody,
                'http_status' => $status,
                'content_type' => $contentType,
                'is_gzipped' => $isGzipped,
                'latency_ms' => $latency,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $latency = (int) round((microtime(true) - $startTime) * 1000);
            return [
                'success' => false,
                'content' => null,
                'http_status' => 0,
                'content_type' => null,
                'is_gzipped' => false,
                'latency_ms' => $latency,
                'error' => "Feed download failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Parse raw CSV / XML feed records streamingly with optional keyword filtering and bounding
     *
     * @return array<int, array<string, mixed>>
     */
    public function parseCsvRecords(
        string $csvContent,
        ?string $keywords = null,
        int $limit = 50
    ): array {
        if (empty(trim($csvContent))) {
            return [];
        }

        // Open string as stream
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        // Read header line
        $headers = fgetcsv($stream, 0, ',');
        if (!$headers || !is_array($headers)) {
            fclose($stream);
            return [];
        }

        // Clean headers
        $cleanHeaders = array_map(function ($h) {
            return trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', (string) $h));
        }, $headers);

        $results = [];
        $searchTerms = $keywords ? array_filter(explode(' ', strtolower(trim($keywords)))) : [];

        while (($row = fgetcsv($stream, 0, ',')) !== false) {
            if (count($row) !== count($cleanHeaders)) {
                // Handle misaligned rows gracefully if possible
                if (count($row) < count($cleanHeaders)) {
                    $row = array_pad($row, count($cleanHeaders), null);
                } else {
                    $row = array_slice($row, 0, count($cleanHeaders));
                }
            }

            $record = array_combine($cleanHeaders, $row);
            if (!$record || empty($record['product_name'] ?? $record['title'] ?? null)) {
                continue;
            }

            // Keyword filter if specified
            if (!empty($searchTerms)) {
                $searchHaystack = strtolower(
                    ($record['product_name'] ?? '') . ' ' .
                    ($record['brand_name'] ?? '') . ' ' .
                    ($record['description'] ?? '') . ' ' .
                    ($record['category_name'] ?? '')
                );

                $allMatch = true;
                foreach ($searchTerms as $term) {
                    if (!str_contains($searchHaystack, $term)) {
                        $allMatch = false;
                        break;
                    }
                }

                if (!$allMatch) {
                    continue;
                }
            }

            $results[] = $record;

            if (count($results) >= $limit) {
                break;
            }
        }

        fclose($stream);
        return $results;
    }
}
