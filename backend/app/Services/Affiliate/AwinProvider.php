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

class AwinProvider implements AffiliateProviderInterface
{
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
        'se' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
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
     * Ingest products from real Awin Product Datafeeds for joined programmes in the target market
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

        $programmes = $this->getJoinedProgrammes($provider);
        if (empty($programmes)) {
            Log::info("Awin search: No joined programmes found for publisher account.");
            return [];
        }

        // Filter programmes matching target market country code if specified
        $targetIso2 = strtoupper($market->code === 'uk' ? 'GB' : $market->code);
        $matchingProgrammes = array_filter($programmes, function ($p) use ($targetIso2) {
            $pCountry = strtoupper($p['primaryRegion']['countryCode'] ?? '');
            return empty($pCountry) || $pCountry === $targetIso2 || $pCountry === 'EU' || $pCountry === 'GLOBAL';
        });

        // If no region-specific match, check all active joined programmes
        if (empty($matchingProgrammes)) {
            $matchingProgrammes = $programmes;
        }

        $results = [];
        $collectedCount = 0;
        $attemptedFeeds = 0;
        $failedFeedErrors = [];

        foreach ($matchingProgrammes as $prog) {
            if ($collectedCount >= $limit) {
                break;
            }

            $attemptedFeeds++;
            $feedUrl = config('services.awin.datafeed_url')
                ?: $this->datafeedService->getFeedUrl($prog['id'], $market);

            $downloadResult = $this->datafeedService->downloadFeed($feedUrl);
            if (!$downloadResult['success']) {
                $status = $downloadResult['http_status'];
                $failedFeedErrors[] = "Advertiser {$prog['id']} ({$prog['name']}): HTTP {$status} ({$downloadResult['error']})";

                if ($status === 401 || $status === 403 || $status >= 500) {
                    throw new RuntimeException("Awin Datafeed Download Error (HTTP {$status}): {$downloadResult['error']}");
                }
                Log::warning("Awin feed download skipped for advertiser {$prog['id']} ({$prog['name']}): {$downloadResult['error']}");
                continue;
            }

            $records = $this->datafeedService->parseCsvRecords(
                $downloadResult['content'],
                $keywords,
                $limit - $collectedCount
            );

            foreach ($records as $record) {
                if (empty($record['merchant_id'])) {
                    $record['merchant_id'] = (string) $prog['id'];
                }
                if (empty($record['merchant_name'])) {
                    $record['merchant_name'] = $prog['name'];
                }
                if (empty($record['merchant_domain']) && !empty($prog['displayUrl'])) {
                    $record['merchant_domain'] = parse_url($prog['displayUrl'], PHP_URL_HOST) ?? $prog['displayUrl'];
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
            throw new RuntimeException("Awin Datafeed unavailable for joined programmes. " . implode('; ', $failedFeedErrors) . ". Please verify AWIN_DATAFEED_API_KEY / AWIN_DATAFEED_URL in .env.");
        }

        return $results;
    }

    public function normalizeAwinItem(array $item, Market $market): ?NormalizedProductDTO
    {
        $id = $item['aw_product_id'] ?? $item['id'] ?? $item['product_id'] ?? null;
        $title = $item['product_name'] ?? $item['title'] ?? null;

        if (!$title || !$id) {
            return null;
        }

        $brandName = $item['brand_name'] ?? $item['manufacturer'] ?? 'Generic';
        $modelNumber = $item['model_number'] ?? $item['mpn'] ?? null;
        $ean = $item['ean'] ?? $item['gtin'] ?? null;
        $upc = $item['upc'] ?? null;
        $mpn = $item['mpn'] ?? null;
        $sku = (string) $id;

        $identifiers = [];
        if ($ean) {
            $identifiers[] = NormalizedIdentifierDTO::from('EAN', (string) $ean);
        }
        if ($upc) {
            $identifiers[] = NormalizedIdentifierDTO::from('UPC', (string) $upc);
        }
        if ($mpn) {
            $identifiers[] = NormalizedIdentifierDTO::from('MPN', (string) $mpn);
        }
        $identifiers[] = NormalizedIdentifierDTO::from('SKU', $sku);

        // Price & Offer
        $price = isset($item['search_price']) ? (float) $item['search_price'] : (isset($item['price']) ? (float) $item['price'] : 0.0);
        $originalPrice = isset($item['retail_price']) ? (float) $item['retail_price'] : null;
        $currency = $item['currency'] ?? $this->marketDefaults[$market->code]['currency'] ?? 'EUR';
        $merchantName = $item['merchant_name'] ?? $item['advertiser_name'] ?? 'Awin Partner';
        $merchantDomain = $item['merchant_domain'] ?? strtolower(str_replace(['http://', 'https://', 'www.', ' '], '', $merchantName)) . '.com';
        $productUrl = $item['aw_deep_link'] ?? $item['deep_link'] ?? $item['product_url'] ?? '';

        $inStock = true;
        if (isset($item['in_stock'])) {
            $inStock = in_array(strtolower((string) $item['in_stock']), ['1', 'true', 'yes', 'in_stock', 'in stock', 'instock']);
        }

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $merchantDomain,
            retailerName: $merchantName,
            sku: $sku,
            title: $title,
            price: $price,
            originalPrice: $originalPrice,
            currencyCode: strtoupper($currency),
            availability: $inStock ? 'in_stock' : 'out_of_stock',
            condition: 'new',
            affiliateUrl: $productUrl,
            originalUrl: $item['merchant_deep_link'] ?? $productUrl,
            shippingCost: isset($item['delivery_cost']) ? (float) $item['delivery_cost'] : 0.0,
            marketCode: $market->code
        );

        $images = [];
        $imgUrl = $item['merchant_image_url'] ?? $item['image_url'] ?? $item['aw_image_url'] ?? null;
        if (!empty($imgUrl)) {
            $images[] = new NormalizedImageDTO($imgUrl, $title, true, 0);
        }

        $specs = [];
        if (!empty($item['specifications']) && is_array($item['specifications'])) {
            foreach ($item['specifications'] as $sName => $sVal) {
                $specs[] = new NormalizedSpecificationDTO('General', (string) $sName, (string) $sVal);
            }
        }

        return new NormalizedProductDTO(
            name: $title,
            brandName: $brandName,
            categorySlug: null,
            modelNumber: $modelNumber,
            description: $item['description'] ?? null,
            shortDescription: substr($title, 0, 250),
            canonicalUpc: $upc ? (string) $upc : null,
            canonicalEan: $ean ? (string) $ean : null,
            canonicalMpn: $mpn ? (string) $mpn : null,
            identifiers: $identifiers,
            specifications: $specs,
            images: $images,
            offer: $offerDto,
            providerCode: 'awin',
            externalId: $sku
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
}
