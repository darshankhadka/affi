<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProvider;
use App\Models\Market;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
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
     * Stream and parse feed records with bounded memory and incremental decompression
     *
     * @return array{
     *   success: bool,
     *   records: array<int, array<string, mixed>>,
     *   http_status: int,
     *   headers: array<string, string>,
     *   compression: string,
     *   bytes_received: int,
     *   latency_ms: int,
     *   ttfb_ms: int,
     *   error_code: ?string,
     *   error: ?string
     * }
     */
    public function streamFeedRecords(
        string $feedUrl,
        ?string $keywords = null,
        ?Market $market = null,
        int $limit = 50,
        ?callable $progressCallback = null
    ): array {
        if (empty($feedUrl)) {
            return [
                'success' => false,
                'records' => [],
                'http_status' => 0,
                'headers' => [],
                'compression' => 'none',
                'bytes_received' => 0,
                'latency_ms' => 0,
                'ttfb_ms' => 0,
                'error_code' => 'missing_configuration',
                'error' => 'Awin Datafeed URL is not configured (set AWIN_DATAFEED_URL in backend/.env).',
            ];
        }

        $t0 = microtime(true);
        $ttfb = 0;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
                'Accept-Encoding' => 'gzip, deflate',
            ])->withOptions([
                'stream' => true,
                'connect_timeout' => 10,
                'read_timeout' => 15,
            ])->timeout(60)->get($feedUrl);

            $ttfb = (int) round((microtime(true) - $t0) * 1000);
            $psrResponse = $response->toPsrResponse();
            $status = $psrResponse->getStatusCode();

            $headerMap = [];
            foreach ($psrResponse->getHeaders() as $name => $values) {
                $headerMap[strtolower($name)] = implode(', ', $values);
            }

            if ($status !== 200) {
                $elapsed = (int) round((microtime(true) - $t0) * 1000);
                $bodyPreview = substr((string) $psrResponse->getBody()->read(500), 0, 300);

                return [
                    'success' => false,
                    'records' => [],
                    'http_status' => $status,
                    'headers' => $headerMap,
                    'compression' => 'none',
                    'bytes_received' => strlen($bodyPreview),
                    'latency_ms' => $elapsed,
                    'ttfb_ms' => $ttfb,
                    'error_code' => $this->classifyHttpStatus($status),
                    'error' => "HTTP {$status}: " . $bodyPreview,
                ];
            }

            $body = $psrResponse->getBody();
            $contentType = $headerMap['content-type'] ?? '';
            $contentDisposition = $headerMap['content-disposition'] ?? '';

            // Detect compression
            $compression = 'none';
            $isGzip = str_contains($contentType, 'gzip') || str_contains($contentDisposition, '.gz');
            $isZip = !$isGzip && (str_contains($contentType, 'zip') || str_contains($contentDisposition, '.zip'));

            if ($isGzip) {
                $compression = 'gzip';
            } elseif ($isZip) {
                $compression = 'zip';
            }

            // ZIP handling (if whole archive needed, buffer safely to temp file)
            if ($isZip) {
                return $this->handleZipStream($body, $keywords, $market, $limit, $headerMap, $t0, $ttfb, $progressCallback);
            }

            // Streaming incremental GZIP / Plain CSV parsing
            $inflator = $isGzip ? @inflate_init(ZLIB_ENCODING_GZIP) : null;
            $buffer = '';
            $headersParsed = false;
            $cleanHeaders = [];
            $records = [];
            $bytesReceived = 0;
            $rowsExamined = 0;
            $rowsSkipped = 0;
            $skipReasons = [];
            $searchTerms = $keywords ? array_filter(explode(' ', strtolower(trim($keywords)))) : [];
            $expectedCurrency = $market ? strtoupper($market->defaultCurrency?->code ?? $market->currency?->code ?? '') : null;

            while (!$body->eof()) {
                $chunk = $body->read(16384);
                if ($chunk === '' || $chunk === false) {
                    break;
                }

                $chunkLen = strlen($chunk);
                $bytesReceived += $chunkLen;

                // Detect gzip magic bytes on first chunk if not signaled in headers
                if ($bytesReceived === $chunkLen && $compression === 'none' && $chunkLen >= 2 && substr($chunk, 0, 2) === "\x1f\x8b") {
                    $compression = 'gzip';
                    $inflator = @inflate_init(ZLIB_ENCODING_GZIP);
                }

                if ($inflator) {
                    $decompressed = @inflate_add($inflator, $chunk, ZLIB_SYNC_FLUSH);
                    if ($decompressed !== false) {
                        $buffer .= $decompressed;
                    } else {
                        $buffer .= $chunk;
                    }
                } else {
                    $buffer .= $chunk;
                }

                // Process complete lines from buffer
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    $line = trim($line, "\r\n");
                    if ($line === '') {
                        continue;
                    }

                    // 1. First line = CSV Header
                    if (!$headersParsed) {
                        $stream = fopen('php://memory', 'r+');
                        fwrite($stream, $line);
                        rewind($stream);
                        $rawH = fgetcsv($stream, 0, ',');
                        fclose($stream);

                        if (!empty($rawH) && is_array($rawH)) {
                            $cleanHeaders = array_map(function ($h) {
                                return trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', (string) $h));
                            }, $rawH);
                            $headersParsed = true;
                        }
                        continue;
                    }

                    $rowsExamined++;

                    // 2. Data line = CSV Row
                    $stream = fopen('php://memory', 'r+');
                    fwrite($stream, $line);
                    rewind($stream);
                    $row = fgetcsv($stream, 0, ',');
                    fclose($stream);

                    if (!is_array($row) || empty($row)) {
                        $rowsSkipped++;
                        $skipReasons['MALFORMED_CSV_ROW'] = ($skipReasons['MALFORMED_CSV_ROW'] ?? 0) + 1;
                        continue;
                    }

                    // Align row column counts
                    $hCount = count($cleanHeaders);
                    $rCount = count($row);
                    if ($rCount !== $hCount) {
                        if ($rCount < $hCount) {
                            $row = array_pad($row, $hCount, null);
                        } else {
                            $row = array_slice($row, 0, $hCount);
                        }
                    }

                    $record = array_combine($cleanHeaders, $row);
                    if (!$record || empty($record['product_name'] ?? $record['title'] ?? null)) {
                        $rowsSkipped++;
                        $skipReasons['MISSING_PRODUCT_NAME'] = ($skipReasons['MISSING_PRODUCT_NAME'] ?? 0) + 1;
                        continue;
                    }

                    // Market currency compatibility filter (e.g. GB expects GBP, DE expects EUR)
                    if ($expectedCurrency && !empty($record['currency'])) {
                        $recCurrency = strtoupper(trim((string) $record['currency']));
                        if ($recCurrency !== $expectedCurrency) {
                            $rowsSkipped++;
                            $skipReasons['MARKET_CURRENCY_MISMATCH'] = ($skipReasons['MARKET_CURRENCY_MISMATCH'] ?? 0) + 1;
                            continue;
                        }
                    }

                    // Keyword filter if requested
                    if (!empty($searchTerms)) {
                        $haystack = strtolower(
                            ($record['product_name'] ?? '') . ' ' .
                            ($record['brand_name'] ?? '') . ' ' .
                            ($record['merchant_category'] ?? '') . ' ' .
                            ($record['category_name'] ?? '') . ' ' .
                            ($record['keywords'] ?? '') . ' ' .
                            ($record['model_number'] ?? '') . ' ' .
                            ($record['mpn'] ?? '') . ' ' .
                            ($record['description'] ?? '')
                        );

                        $allMatch = true;
                        foreach ($searchTerms as $term) {
                            if (!str_contains($haystack, $term)) {
                                $allMatch = false;
                                break;
                            }
                        }

                        if (!$allMatch) {
                            $rowsSkipped++;
                            $skipReasons['KEYWORD_MISMATCH'] = ($skipReasons['KEYWORD_MISMATCH'] ?? 0) + 1;
                            continue;
                        }
                    }

                    $records[] = $record;

                    if ($progressCallback) {
                        $progressCallback([
                            'bytes_received' => $bytesReceived,
                            'elapsed_ms' => (int) round((microtime(true) - $t0) * 1000),
                            'rows_examined' => $rowsExamined,
                            'rows_parsed' => count($records),
                            'rows_skipped' => $rowsSkipped,
                        ]);
                    }

                    if (count($records) >= $limit) {
                        break 2;
                    }
                }
            }

            $body->close();
            $elapsed = (int) round((microtime(true) - $t0) * 1000);

            return [
                'success' => true,
                'records' => $records,
                'http_status' => 200,
                'headers' => $headerMap,
                'compression' => $compression,
                'bytes_received' => $bytesReceived,
                'rows_examined' => $rowsExamined,
                'rows_accepted' => count($records),
                'rows_skipped' => $rowsSkipped,
                'skip_reasons' => $skipReasons,
                'latency_ms' => $elapsed,
                'ttfb_ms' => $ttfb,
                'error_code' => null,
                'error' => null,
            ];
        } catch (ConnectException $ce) {
            $elapsed = (int) round((microtime(true) - $t0) * 1000);
            return [
                'success' => false,
                'records' => [],
                'http_status' => 0,
                'headers' => [],
                'compression' => 'none',
                'bytes_received' => 0,
                'latency_ms' => $elapsed,
                'ttfb_ms' => 0,
                'error_code' => 'connection_timeout',
                'error' => 'Connection timeout: Could not connect to Awin feed server within timeout.',
            ];
        } catch (RequestException $re) {
            $elapsed = (int) round((microtime(true) - $t0) * 1000);
            $status = $re->hasResponse() ? $re->getResponse()->getStatusCode() : 0;
            return [
                'success' => false,
                'records' => [],
                'http_status' => $status,
                'headers' => [],
                'compression' => 'none',
                'bytes_received' => 0,
                'latency_ms' => $elapsed,
                'ttfb_ms' => 0,
                'error_code' => $this->classifyHttpStatus($status),
                'error' => "Request failure (HTTP {$status}): " . $re->getMessage(),
            ];
        } catch (Throwable $e) {
            $elapsed = (int) round((microtime(true) - $t0) * 1000);
            return [
                'success' => false,
                'records' => [],
                'http_status' => 0,
                'headers' => [],
                'compression' => 'none',
                'bytes_received' => 0,
                'latency_ms' => $elapsed,
                'ttfb_ms' => 0,
                'error_code' => 'stream_error',
                'error' => "Datafeed streaming exception: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Stream and extract CSV entry from a ZIP archive stream
     */
    protected function handleZipStream(
        $body,
        ?string $keywords,
        ?Market $market,
        int $limit,
        array $headers,
        float $t0,
        int $ttfb,
        ?callable $progressCallback
    ): array {
        $tmpZip = tempnam(sys_get_temp_dir(), 'awin_zip_') . '.zip';
        $out = fopen($tmpZip, 'wb');
        $bytesReceived = 0;

        while (!$body->eof()) {
            $chunk = $body->read(32768);
            if ($chunk === '' || $chunk === false) {
                break;
            }
            fwrite($out, $chunk);
            $bytesReceived += strlen($chunk);
        }
        fclose($out);
        $body->close();

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            $elapsed = (int) round((microtime(true) - $t0) * 1000);
            return [
                'success' => false,
                'records' => [],
                'http_status' => 200,
                'headers' => $headers,
                'compression' => 'zip',
                'bytes_received' => $bytesReceived,
                'latency_ms' => $elapsed,
                'ttfb_ms' => $ttfb,
                'error_code' => 'zip_extract_error',
                'error' => 'Could not open ZIP archive from Awin feed response.',
            ];
        }

        // Find CSV file in ZIP archive
        $csvIndex = -1;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with(strtolower($name), '.csv') || str_ends_with(strtolower($name), '.txt')) {
                $csvIndex = $i;
                break;
            }
        }

        if ($csvIndex === -1 && $zip->numFiles > 0) {
            $csvIndex = 0;
        }

        $csvStream = $zip->getStream($zip->getNameIndex($csvIndex));

        if (!$csvStream) {
            $zip->close();
            @unlink($tmpZip);
            $elapsed = (int) round((microtime(true) - $t0) * 1000);
            return [
                'success' => false,
                'records' => [],
                'http_status' => 200,
                'headers' => $headers,
                'compression' => 'zip',
                'bytes_received' => $bytesReceived,
                'latency_ms' => $elapsed,
                'ttfb_ms' => $ttfb,
                'error_code' => 'zip_empty',
                'error' => 'No readable CSV file found inside ZIP feed archive.',
            ];
        }

        // Parse extracted CSV stream
        $rawHeaders = fgetcsv($csvStream, 0, ',');
        $cleanHeaders = array_map(function ($h) {
            return trim(str_replace(["\xEF\xBB\xBF", '"', "'"], '', (string) $h));
        }, (array) $rawHeaders);

        $records = [];
        $searchTerms = $keywords ? array_filter(explode(' ', strtolower(trim($keywords)))) : [];

        while (($row = fgetcsv($csvStream, 0, ',')) !== false && count($records) < $limit) {
            if (count($row) !== count($cleanHeaders)) {
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

            if (!empty($searchTerms)) {
                $haystack = strtolower(
                    ($record['product_name'] ?? '') . ' ' .
                    ($record['brand_name'] ?? '') . ' ' .
                    ($record['description'] ?? '')
                );
                $allMatch = true;
                foreach ($searchTerms as $term) {
                    if (!str_contains($haystack, $term)) {
                        $allMatch = false;
                        break;
                    }
                }
                if (!$allMatch) {
                    continue;
                }
            }

            $records[] = $record;
        }

        fclose($csvStream);
        $zip->close();
        @unlink($tmpZip);
        $elapsed = (int) round((microtime(true) - $t0) * 1000);

        return [
            'success' => true,
            'records' => $records,
            'http_status' => 200,
            'headers' => $headers,
            'compression' => 'zip',
            'bytes_received' => $bytesReceived,
            'latency_ms' => $elapsed,
            'ttfb_ms' => $ttfb,
            'error_code' => null,
            'error' => null,
        ];
    }

    /**
     * Map HTTP status codes into standardized error classification
     */
    protected function classifyHttpStatus(int $status): string
    {
        return match ($status) {
            401 => 'invalid_credentials',
            403 => 'forbidden',
            404 => 'feed_not_found',
            429 => 'rate_limited',
            500, 502, 503, 504 => 'server_error',
            default => $status >= 400 ? 'http_error' : 'unknown_error',
        };
    }
}
