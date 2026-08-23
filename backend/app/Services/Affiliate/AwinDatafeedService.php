<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProvider;
use App\Models\Market;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;
use ZipArchive;

class AwinDatafeedService
{
    /**
     * Comprehensive Awin Create-a-Feed column list
     */
    public const COMPREHENSIVE_FEED_COLUMNS = [
        // CORE
        'aw_deep_link',
        'product_name',
        'aw_product_id',
        'merchant_product_id',
        'merchant_image_url',
        'description',
        'merchant_category',
        'search_price',

        // RECOMMENDED
        'merchant_name',
        'merchant_id',
        'category_name',
        'category_id',
        'aw_image_url',
        'currency',
        'store_price',
        'delivery_cost',
        'merchant_deep_link',
        'language',
        'last_updated',
        'display_price',
        'data_feed_id',

        // PRODUCT
        'brand_name',
        'brand_id',
        'colour',
        'product_short_description',
        'specifications',
        'condition',
        'product_model',
        'model_number',
        'dimensions',
        'keywords',
        'promotional_text',
        'product_type',

        // CATEGORY
        'commission_group',
        'merchant_product_category_path',
        'merchant_product_second_category',
        'merchant_product_third_category',

        // PRICES
        'rrp_price',
        'saving',
        'savings_percent',
        'base_price',
        'base_price_amount',
        'base_price_text',
        'product_price_old',

        // DELIVERY
        'delivery_restrictions',
        'delivery_weight',
        'warranty',
        'terms_of_contract',
        'delivery_time',

        // AVAILABILITY
        'in_stock',
        'stock_quantity',
        'valid_from',
        'valid_to',
        'is_for_sale',
        'web_offer',
        'pre_order',
        'stock_status',
        'size_stock_status',
        'size_stock_amount',

        // IMAGES
        'merchant_thumb_url',
        'large_image',
        'alternate_image',
        'aw_thumb_url',
        'alternate_image_two',
        'alternate_image_three',
        'alternate_image_four',

        // RATINGS
        'reviews',
        'average_rating',
        'rating',
        'number_available',

        // IDENTIFIERS
        'ean',
        'isbn',
        'upc',
        'mpn',
        'parent_product_id',
        'product_GTIN',

        // OTHER
        'basket_link',
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
     * Resolve the feed URL for an advertiser
     */
    public function getFeedUrl(
        int|string $advertiserId,
        Market $market,
        ?string $apiKey = null,
        bool $gzip = true
    ): string {
        $customUrl = config('services.awin.datafeed_url');
        if (!empty($customUrl)) {
            return $customUrl;
        }

        $key = $apiKey ?: config('services.awin.datafeed_api_key');
        if (empty($key)) {
            return '';
        }

        $lang = in_array($market->code, ['gb', 'uk', 'ie']) ? 'en' : $market->code;
        $cols = implode(',', self::COMPREHENSIVE_FEED_COLUMNS);
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
     *   compression: string,
     *   latency_ms: int,
     *   error: ?string
     * }
     */
    public function downloadFeed(string $feedUrl, int $timeoutSeconds = 30): array
    {
        if (empty($feedUrl)) {
            return [
                'success' => false,
                'content' => null,
                'http_status' => 0,
                'content_type' => null,
                'compression' => 'none',
                'latency_ms' => 0,
                'error' => 'Awin Datafeed URL or Datafeed API Key is not configured (AWIN_DATAFEED_URL or AWIN_DATAFEED_API_KEY required).',
            ];
        }

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
                    'compression' => 'none',
                    'latency_ms' => $latency,
                    'error' => "HTTP {$status}: " . substr($response->body(), 0, 300),
                ];
            }

            $rawBody = $response->body();
            $compression = 'none';

            // 1. Detect GZIP magic header (0x1f, 0x8b)
            if (strlen($rawBody) >= 2 && substr($rawBody, 0, 2) === "\x1f\x8b") {
                $compression = 'gzip';
                $decoded = @gzdecode($rawBody);
                if ($decoded !== false) {
                    $rawBody = $decoded;
                } else {
                    return [
                        'success' => false,
                        'content' => null,
                        'http_status' => $status,
                        'content_type' => $contentType,
                        'compression' => 'gzip',
                        'latency_ms' => $latency,
                        'error' => "Failed to decompress GZIP feed content.",
                    ];
                }
            }
            // 2. Detect ZIP magic header (PK\x03\x04)
            elseif (strlen($rawBody) >= 4 && substr($rawBody, 0, 4) === "PK\x03\x04") {
                $compression = 'zip';
                $tmpZip = tempnam(sys_get_temp_dir(), 'awin_zip_');
                file_put_contents($tmpZip, $rawBody);

                $zip = new ZipArchive();
                if ($zip->open($tmpZip) === true) {
                    // Extract first file in zip
                    $filename = $zip->getNameIndex(0);
                    $extracted = $filename ? $zip->getFromIndex(0) : false;
                    $zip->close();
                    @unlink($tmpZip);

                    if ($extracted !== false) {
                        $rawBody = $extracted;
                    } else {
                        return [
                            'success' => false,
                            'content' => null,
                            'http_status' => $status,
                            'content_type' => $contentType,
                            'compression' => 'zip',
                            'latency_ms' => $latency,
                            'error' => "Failed to extract CSV from ZIP feed archive.",
                        ];
                    }
                } else {
                    @unlink($tmpZip);
                    return [
                        'success' => false,
                        'content' => null,
                        'http_status' => $status,
                        'content_type' => $contentType,
                        'compression' => 'zip',
                        'latency_ms' => $latency,
                        'error' => "Could not open ZIP feed archive.",
                    ];
                }
            }

            return [
                'success' => true,
                'content' => $rawBody,
                'http_status' => $status,
                'content_type' => $contentType,
                'compression' => $compression,
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
                'compression' => 'none',
                'latency_ms' => $latency,
                'error' => "Feed download failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Parse raw CSV feed records streamingly with optional keyword filtering and bounding
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

        // Open string as streaming resource to avoid in-memory explosion
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvContent);
        rewind($stream);

        // Read header line
        $headers = fgetcsv($stream, 0, ',');
        if (!$headers || !is_array($headers)) {
            fclose($stream);
            return [];
        }

        // Clean headers: remove UTF-8 BOM, spaces, and quotes
        $cleanHeaders = array_map(function ($h) {
            return trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', (string) $h));
        }, $headers);

        $results = [];
        $searchTerms = $keywords ? array_filter(explode(' ', strtolower(trim($keywords)))) : [];

        while (($row = fgetcsv($stream, 0, ',')) !== false) {
            if (count($row) !== count($cleanHeaders)) {
                // Handle misaligned rows gracefully
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
                    ($record['merchant_category'] ?? '') . ' ' .
                    ($record['category_name'] ?? '') . ' ' .
                    ($record['keywords'] ?? '') . ' ' .
                    ($record['description'] ?? '')
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
