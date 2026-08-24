<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedIdentifierDTO;
use App\DTOs\NormalizedImageDTO;
use App\DTOs\NormalizedOfferDTO;
use App\DTOs\NormalizedProductDTO;
use App\DTOs\NormalizedSpecificationDTO;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AwinProvider extends BaseAffiliateProvider
{
    public function supportsProductFeed(): bool
    {
        return true;
    }
    /**
     * Regional Awin feed/API currency defaults (Europe + UK focused)
     */
    protected array $marketDefaults = [
        'gb' => ['currency' => 'GBP', 'domain' => 'awin1.com'],
        'uk' => ['currency' => 'GBP', 'domain' => 'awin1.com'],
        'de' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'fr' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'it' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'es' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'nl' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'be' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'at' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'ie' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'pt' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'fi' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'se' => ['currency' => 'SEK', 'domain' => 'awin1.com'],
        'dk' => ['currency' => 'DKK', 'domain' => 'awin1.com'],
        'pl' => ['currency' => 'PLN', 'domain' => 'awin1.com'],
        'cz' => ['currency' => 'CZK', 'domain' => 'awin1.com'],
        'us' => ['currency' => 'USD', 'domain' => 'awin1.com'],
    ];

    public function __construct(
        protected ?AwinDatafeedService $datafeedService = null
    ) {
        $this->datafeedService = $datafeedService ?? new AwinDatafeedService();
    }

    public function getCode(): string
    {
        return 'awin';
    }

    public function getName(): string
    {
        return 'Awin Publisher Network';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.awin.api_token');
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        return !empty($apiToken) && !empty($publisherId);
    }

    /**
     * Get joined advertiser programmes
     */
    public function getJoinedProgrammes(?AffiliateProvider $provider = null): array
    {
        $provider = $provider ?? AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        return $this->datafeedService->getJoinedProgrammes($provider);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $startTime = microtime(true);

        if (!$this->isConnected($provider)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Awin API credentials (API Token / Publisher ID) are not configured.',
                'latency_ms' => null,
            ];
        }

        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.awin.api_token');
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'User-Agent' => 'ARIKARTECH-ProductEngine/1.0',
            ])->timeout(8)->get("https://api.awin.com/publishers/{$publisherId}/programmes", [
                'relationship' => 'joined',
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);
            $status = $response->status();

            if ($response->successful()) {
                $programmes = $response->json();
                $count = is_array($programmes) ? count($programmes) : 0;
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => "Successfully connected to Awin Publisher API ({$count} joined programmes).",
                    'latency_ms' => $latency,
                    'programmes_count' => $count,
                ];
            }

            if ($status === 401) {
                return [
                    'connected' => false,
                    'status' => 'invalid_credentials',
                    'message' => 'Awin API authentication failed (401 Unauthorized). Please verify API Token.',
                    'latency_ms' => $latency,
                ];
            }

            if ($status === 403) {
                return [
                    'connected' => false,
                    'status' => 'endpoint_not_permitted',
                    'message' => 'Awin API returned 403 Forbidden: Endpoint or scope not permitted for this account/token policy.',
                    'latency_ms' => $latency,
                ];
            }

            if ($status === 404) {
                return [
                    'connected' => false,
                    'status' => 'endpoint_not_found',
                    'message' => 'Awin API returned 404: Endpoint not found.',
                    'latency_ms' => $latency,
                ];
            }

            if ($status === 429) {
                return [
                    'connected' => false,
                    'status' => 'rate_limited',
                    'message' => 'Awin API returned 429: Rate limited.',
                    'latency_ms' => $latency,
                ];
            }

            if ($status >= 500) {
                return [
                    'connected' => false,
                    'status' => 'provider_server_error',
                    'message' => "Awin API Server Error (HTTP {$status}): " . substr($response->body(), 0, 200),
                    'latency_ms' => $latency,
                ];
            }

            return [
                'connected' => false,
                'status' => 'provider_request_error',
                'message' => "Awin API Request Error (HTTP {$status}): " . substr($response->body(), 0, 200),
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'connected' => false,
                'status' => 'error',
                'message' => "Connection failed: {$e->getMessage()}",
                'latency_ms' => (int) round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string
    {
        $targetUrl = $offer->original_url ?: $offer->affiliate_url;
        if (empty($targetUrl)) {
            return '';
        }

        $provider = $offer->retailer?->affiliateProvider;
        $config = $provider?->config ?? [];
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        if (empty($publisherId)) {
            return $targetUrl;
        }

        $advertiserId = $offer->retailer?->affiliate_program_id ?? $config['default_advertiser_id'] ?? null;
        if (empty($advertiserId)) {
            Log::warning("Awin affiliate URL generation skipped: No verified advertiser program ID found for retailer '{$offer->retailer?->name}'. Using target URL.");
            return $targetUrl;
        }

        $encodedTarget = urlencode($targetUrl);
        $clickRef = $customSubId ? preg_replace('/[^a-zA-Z0-9_-]/', '', substr($customSubId, 0, 50)) : 'arikartech';

        return "https://www.awin1.com/cread.php?awinmid={$advertiserId}&awinaffid={$publisherId}&clickref={$clickRef}&ued={$encodedTarget}";
    }

    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        return [];
    }

    /**
     * Ingest products from Awin feeds for joined programmes.
     *
     * Supports two feed strategies:
     *  - Architecture A (publisher-wide): configured AWIN_DATAFEED_URL.
     *    The feed is downloaded EXACTLY ONCE, streamed, and every record's own
     *    merchant/programme is identified from the feed row (no per-advertiser HTTP).
     *  - Architecture B (advertiser-specific): /mid/{advertiserId} feeds built from
     *    AWIN_DATAFEED_API_KEY. One per joined programme (only when no publisher-wide feed).
     *
     * @return NormalizedProductDTO[]
     * @throws RuntimeException on API/network failure
     */
    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$this->isConnected($provider)) {
            throw new RuntimeException("Awin provider is disconnected or missing credentials.");
        }

        $feedSource = $this->datafeedService->resolveFeedSource($provider, null, $market);
        if (!$feedSource) {
            throw new RuntimeException(
                "Awin feed is not configured. Set AWIN_DATAFEED_URL (publisher-wide) or AWIN_DATAFEED_API_KEY (advertiser feeds)."
            );
        }

        // Architecture A: publisher-wide, shared feed -> download once.
        if ($feedSource->isShared()) {
            return $this->searchWithSharedFeed($feedSource, $keywords, $market, $limit);
        }

        // Architecture B: per-advertiser feeds (only when no publisher-wide feed is configured).
        return $this->searchWithAdvertiserFeeds($provider, $feedSource, $keywords, $market, $limit);
    }

    /**
     * Architecture A — download the publisher-wide feed a single time and normalize every
     * relevant record. Merchant/programme identity comes from each feed row, not from a
     * pre-filtered programme list, so the same download serves every joined advertiser.
     */
    protected function searchWithSharedFeed(
        \App\Services\Affiliate\Feeds\FeedSource $feedSource,
        string $keywords,
        Market $market,
        int $limit
    ): array {
        $streamResult = $this->datafeedService->streamFeedRecords($feedSource, $keywords, $market, $limit);

        if (!$streamResult['success']) {
            $status = $streamResult['http_status'];
            // Do NOT treat 401/403/404/5xx as an empty catalog — surface the failure.
            throw new RuntimeException(
                "Awin publisher-wide datafeed download failed (HTTP {$status}): " . ($streamResult['error'] ?? 'unknown error')
            );
        }

        $acceptedAdvertiserIds = $feedSource->getAdvertiserIds();
        $results = [];
        $collectedCount = 0;

        foreach ($streamResult['records'] as $record) {
            // Optional scope to a subset of joined advertisers if explicitly configured.
            if (!empty($acceptedAdvertiserIds)) {
                $rowAdvertiser = (int) ($record['merchant_id'] ?? 0);
                if (!in_array($rowAdvertiser, $acceptedAdvertiserIds, true)) {
                    continue;
                }
            }

            if (empty($record['merchant_id'])) {
                $record['merchant_id'] = (string) ($record['merchant_id'] ?? $feedSource->getAdvertiserIds()[0] ?? 0);
            }
            if (empty($record['merchant_name'])) {
                $record['merchant_name'] = $record['merchant_name'] ?? 'Awin Partner';
            }

            $dto = $this->normalizeAwinItem($record, $market);
            if ($dto) {
                $results[] = $dto;
                $collectedCount++;
                if ($collectedCount >= $limit) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Architecture B — one /mid/{advertiserId} feed per joined programme.
     * Only reached when no publisher-wide feed URL is configured.
     */
    protected function searchWithAdvertiserFeeds(
        AffiliateProvider $provider,
        \App\Services\Affiliate\Feeds\FeedSource $templateSource,
        string $keywords,
        Market $market,
        int $limit
    ): array {
        $programmes = $this->getJoinedProgrammes($provider);
        if (empty($programmes)) {
            Log::info("Awin search: No joined programmes found for publisher account.");
            return [];
        }

        // Filter programmes matching target market country code if specified.
        $targetIso2 = strtoupper($market->code === 'uk' ? 'GB' : $market->code);
        $matchingProgrammes = array_filter($programmes, function ($p) use ($targetIso2) {
            $pCountry = strtoupper($p['primaryRegion']['countryCode'] ?? '');
            return empty($pCountry) || $pCountry === $targetIso2 || $pCountry === 'EU' || $pCountry === 'GLOBAL';
        });

        if (empty($matchingProgrammes)) {
            $matchingProgrammes = $programmes;
        }

        $matchingIds = array_column($matchingProgrammes, 'id');
        $feeds = $this->datafeedService->resolveAdvertiserFeedSources($provider, $matchingIds, $market);

        if (empty($feeds)) {
            throw new RuntimeException(
                "Awin advertiser datafeeds could not be built. Verify AWIN_DATAFEED_API_KEY is configured."
            );
        }

        $results = [];
        $collectedCount = 0;
        $failedFeedErrors = [];

        foreach ($matchingProgrammes as $prog) {
            if ($collectedCount >= $limit) {
                break;
            }

            $feedUrl = $this->datafeedService->resolveFeedSource($provider, (int) $prog['id'], $market)?->getUrl();
            if (empty($feedUrl)) {
                $failedFeedErrors[] = "Advertiser {$prog['id']} ({$prog['name']}): Datafeed URL is not configured (set AWIN_DATAFEED_API_KEY)";
                continue;
            }

            $streamResult = $this->datafeedService->streamFeedRecords($feedUrl, $keywords, $market, $limit - $collectedCount);

            if (!$streamResult['success']) {
                $status = $streamResult['http_status'];
                $failedFeedErrors[] = "Advertiser {$prog['id']} ({$prog['name']}): HTTP {$status} ({$streamResult['error']})";

                if ($status === 401 || $status === 403 || $status >= 500) {
                    throw new RuntimeException("Awin Datafeed Download Error (HTTP {$status}): {$streamResult['error']}");
                }
                Log::warning("Awin feed stream skipped for advertiser {$prog['id']} ({$prog['name']}): {$streamResult['error']}");
                continue;
            }

            foreach ($streamResult['records'] as $record) {
                if (empty($record['merchant_id'])) {
                    $record['merchant_id'] = (string) $prog['id'];
                }
                if (empty($record['merchant_name'])) {
                    $record['merchant_name'] = $prog['name'];
                }

                $dto = $this->normalizeAwinItem($record, $market);
                if ($dto) {
                    $results[] = $dto;
                    $collectedCount++;
                    if ($collectedCount >= $limit) {
                        break;
                    }
                }
            }
        }

        if (empty($results) && !empty($failedFeedErrors) && $collectedCount === 0) {
            throw new RuntimeException("Awin Datafeed unavailable for joined programmes. " . implode('; ', $failedFeedErrors) . ".");
        }

        return $results;
    }

    /**
     * Normalize an individual Awin Create-a-Feed item into canonical DTO
     */
    public function normalizeAwinItem(array $item, Market $market): ?NormalizedProductDTO
    {
        $id = $item['aw_product_id'] ?? $item['merchant_product_id'] ?? null;
        $title = $item['product_name'] ?? $item['title'] ?? null;

        if (!$title || !$id) {
            return null;
        }

        $brandName = $item['brand_name'] ?? $item['brand_id'] ?? $item['manufacturer'] ?? 'Generic';
        $modelNumber = $item['model_number'] ?? $item['product_model'] ?? $item['mpn'] ?? null;
        $description = $item['description'] ?? $item['product_short_description'] ?? null;
        $shortDesc = $item['product_short_description'] ?? substr($title, 0, 250);

        // Identifiers in priority hierarchy
        $gtin = $item['product_GTIN'] ?? null;
        $ean = $gtin ?: ($item['ean'] ?? $item['isbn'] ?? null);
        $upc = $item['upc'] ?? null;
        $mpn = $item['mpn'] ?? $modelNumber;
        $merchantProductId = $item['merchant_product_id'] ?? null;
        $awProductId = (string) $id;

        $identifiers = [];
        if ($gtin) {
            $identifiers[] = NormalizedIdentifierDTO::from('GTIN', (string) $gtin);
        }
        if ($ean) {
            $identifiers[] = NormalizedIdentifierDTO::from('EAN', (string) $ean);
        }
        if ($upc) {
            $identifiers[] = NormalizedIdentifierDTO::from('UPC', (string) $upc);
        }
        if ($mpn) {
            $identifiers[] = NormalizedIdentifierDTO::from('MPN', (string) $mpn);
        }
        if ($merchantProductId) {
            $identifiers[] = NormalizedIdentifierDTO::from('SKU', (string) $merchantProductId);
        }
        if ($awProductId && $awProductId !== $merchantProductId) {
            $identifiers[] = NormalizedIdentifierDTO::from('SKU', $awProductId);
        }

        // Price & Offer
        $price = isset($item['search_price']) ? (float) $item['search_price'] : (
            isset($item['store_price']) ? (float) $item['store_price'] : (
                isset($item['display_price']) ? (float) $item['display_price'] : (
                    isset($item['price']) ? (float) $item['price'] : 0.0
                )
            )
        );

        $originalPrice = isset($item['rrp_price']) ? (float) $item['rrp_price'] : (
            isset($item['product_price_old']) ? (float) $item['product_price_old'] : null
        );

        $currency = $item['currency'] ?? $this->marketDefaults[$market->code]['currency'] ?? 'EUR';
        $merchantName = $item['merchant_name'] ?? $item['merchant_id'] ?? 'Awin Partner';
        
        // Accurate Merchant Domain Extraction: Never blindly use programme website
        $merchantDomain = null;
        if (!empty($item['merchant_deep_link'])) {
            $merchantDomain = parse_url($item['merchant_deep_link'], PHP_URL_HOST);
        } elseif (!empty($item['product_url'])) {
            $merchantDomain = parse_url($item['product_url'], PHP_URL_HOST);
        } elseif (!empty($item['aw_deep_link'])) {
            $parts = parse_url($item['aw_deep_link']);
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
                if (!empty($query['ued'])) {
                    $merchantDomain = parse_url($query['ued'], PHP_URL_HOST);
                }
            }
        }

        // Clean & normalize domain
        if (!empty($merchantDomain)) {
            $merchantDomain = preg_replace('/^www\./i', '', strtolower(trim($merchantDomain)));
            // Remove port if present
            $merchantDomain = preg_replace('/:\d+$/', '', $merchantDomain);
        }

        // Fallback only if no destination URL exists in feed row
        if (empty($merchantDomain) || in_array($merchantDomain, ['awin1.com', 'productserve.com', 'zenaps.com'])) {
            if ((string) ($item['merchant_id'] ?? '') === '25962' || str_contains(strtolower((string) $merchantName), 'blazevideo')) {
                $merchantDomain = 'blazevideos.de';
            } else {
                $cleanName = strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $merchantName));
                $merchantDomain = $cleanName . ($market->code === 'gb' ? '.co.uk' : '.com');
            }
        }

        // Deep links
        $affiliateUrl = $item['aw_deep_link'] ?? $item['deep_link'] ?? $item['product_url'] ?? '';
        $originalUrl = $item['merchant_deep_link'] ?? $affiliateUrl;

        // Stock / Availability
        $inStock = true;
        if (isset($item['in_stock'])) {
            $inStock = in_array(strtolower((string) $item['in_stock']), ['1', 'true', 'yes', 'in_stock', 'in stock', 'instock']);
        } elseif (isset($item['stock_status'])) {
            $inStock = strtolower((string) $item['stock_status']) !== 'out of stock';
        } elseif (isset($item['is_for_sale'])) {
            $inStock = in_array(strtolower((string) $item['is_for_sale']), ['1', 'true', 'yes']);
        }

        // Delivery
        $deliveryCost = isset($item['delivery_cost']) ? (float) $item['delivery_cost'] : 0.0;

        $condition = !empty($item['condition']) ? strtolower(trim((string) $item['condition'])) : 'new';
        if (!in_array($condition, ['new', 'refurbished', 'used', 'open_box'])) {
            $condition = 'new';
        }

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $merchantDomain,
            retailerName: $merchantName,
            sku: $merchantProductId ?: $awProductId,
            title: $title,
            price: $price,
            originalPrice: $originalPrice,
            currencyCode: strtoupper((string) $currency),
            availability: $inStock ? 'in_stock' : 'out_of_stock',
            condition: $condition,
            affiliateUrl: $affiliateUrl,
            originalUrl: $originalUrl,
            shippingCost: $deliveryCost,
            marketCode: $market->code,
            merchantId: isset($item['merchant_id']) ? (string) $item['merchant_id'] : null
        );

        // Images (handle single and comma-separated image lists safely)
        $rawImages = [];
        if (!empty($item['merchant_image_url'])) {
            $rawImages[] = $item['merchant_image_url'];
        }
        if (!empty($item['large_image'])) {
            $rawImages[] = $item['large_image'];
        }
        if (!empty($item['aw_image_url'])) {
            $rawImages[] = $item['aw_image_url'];
        }
        if (!empty($item['alternate_image'])) {
            $rawImages = array_merge($rawImages, explode(',', (string) $item['alternate_image']));
        }
        if (!empty($item['alternate_image_two'])) {
            $rawImages = array_merge($rawImages, explode(',', (string) $item['alternate_image_two']));
        }
        if (!empty($item['alternate_image_three'])) {
            $rawImages = array_merge($rawImages, explode(',', (string) $item['alternate_image_three']));
        }
        if (!empty($item['alternate_image_four'])) {
            $rawImages = array_merge($rawImages, explode(',', (string) $item['alternate_image_four']));
        }
        if (!empty($item['merchant_thumb_url'])) {
            $rawImages[] = $item['merchant_thumb_url'];
        }

        $images = [];
        $seenUrls = [];
        $imgOrder = 0;

        foreach ($rawImages as $rawUrl) {
            $cleanUrl = trim((string) $rawUrl);
            if (empty($cleanUrl) || !filter_var($cleanUrl, FILTER_VALIDATE_URL) || isset($seenUrls[$cleanUrl])) {
                continue;
            }
            $seenUrls[$cleanUrl] = true;
            $images[] = new NormalizedImageDTO($cleanUrl, $title, $imgOrder === 0, $imgOrder++);
            if (count($images) >= 5) {
                break;
            }
        }

        // Specifications
        $specs = [];
        if (!empty($item['colour'])) {
            $specs[] = new NormalizedSpecificationDTO('Physical', 'Colour', (string) $item['colour']);
        }
        if (!empty($item['dimensions'])) {
            $specs[] = new NormalizedSpecificationDTO('Physical', 'Dimensions', (string) $item['dimensions']);
        }
        if (!empty($item['delivery_weight'])) {
            $specs[] = new NormalizedSpecificationDTO('Physical', 'Weight', (string) $item['delivery_weight']);
        }
        if (!empty($item['warranty'])) {
            $specs[] = new NormalizedSpecificationDTO('General', 'Warranty', (string) $item['warranty']);
        }
        if (!empty($item['specifications']) && is_array($item['specifications'])) {
            foreach ($item['specifications'] as $sName => $sVal) {
                $specs[] = new NormalizedSpecificationDTO('Technical', (string) $sName, (string) $sVal);
            }
        }

        return new NormalizedProductDTO(
            name: $title,
            brandName: $brandName,
            categorySlug: null,
            modelNumber: $modelNumber,
            description: $description,
            shortDescription: $shortDesc,
            canonicalUpc: $upc ? (string) $upc : null,
            canonicalEan: $ean ? (string) $ean : null,
            canonicalMpn: $mpn ? (string) $mpn : null,
            identifiers: $identifiers,
            specifications: $specs,
            images: $images,
            offer: $offerDto,
            providerCode: 'awin',
            externalId: $awProductId
        );
    }

    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array
    {
        return [
            'processed' => 0,
            'items' => [],
            'next_cursor' => null,
            'has_more' => false,
        ];
    }

    public function getRateLimit(): int
    {
        return 60; // 60 requests per minute
    }

    public function getSupportedMarkets(): array
    {
        return ['gb', 'de', 'fr', 'nl', 'es', 'it', 'be', 'at', 'ie', 'pt', 'fi', 'se', 'dk', 'pl', 'cz', 'us', 'uk'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['GBP', 'EUR', 'DKK', 'PLN', 'CZK', 'USD'];
    }

    public function getSupportedCategories(): array
    {
        return ['Computers', 'Laptops', 'PC Components', 'Monitors', 'Peripherals', 'Smartphones', 'Audio', 'TVs', 'Electronic Accessories'];
    }

    /**
     * Safe diagnostic describing the resolved feed strategy (no secrets).
     */
    public function getFeedDiagnostics(\App\Models\AffiliateProvider $provider): array
    {
        return $this->datafeedService->getFeedDiagnostics($provider);
    }
}
