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
use Throwable;

class CjProvider extends BaseAffiliateProvider
{
    protected array $marketCurrencies = [
        'us' => 'USD',
        'uk' => 'GBP',
        'de' => 'EUR',
        'fr' => 'EUR',
        'es' => 'EUR',
        'it' => 'EUR',
        'nl' => 'EUR',
        'au' => 'AUD',
        'nz' => 'NZD',
    ];

    public function getCode(): string
    {
        return 'cj';
    }

    public function getName(): string
    {
        return 'CJ Affiliate (Commission Junction)';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.cj.api_token');
        $companyId = $config['company_id'] ?? config('services.cj.company_id');

        return !empty($apiToken) && !empty($companyId);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $startTime = microtime(true);

        if (!$this->isConnected($provider)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'CJ Affiliate credentials (Personal Access Token / Company ID) are not configured.',
                'latency_ms' => null,
            ];
        }

        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.cj.api_token');
        $companyId = $config['company_id'] ?? config('services.cj.company_id');

        // GraphQL Query for testing authentication
        $query = '
            query {
                publisher {
                    companyName
                    companyId
                }
            }
        ';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type' => 'application/json',
            ])->timeout(6)->post('https://ads.api.cj.com/query', [
                'query' => $query,
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful() && !isset($response->json()['errors'])) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'Successfully connected to CJ Affiliate GraphQL API.',
                    'latency_ms' => $latency,
                ];
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'connected' => false,
                    'status' => 'invalid_credentials',
                    'message' => 'CJ Affiliate authentication failed. Verify Personal Access Token.',
                    'latency_ms' => $latency,
                ];
            }

            $errorMsg = $response->json()['errors'][0]['message'] ?? "HTTP error {$response->status()}";
            return [
                'connected' => false,
                'status' => 'error',
                'message' => "CJ API returned: {$errorMsg}",
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
        $websiteId = $config['website_id'] ?? config('services.cj.website_id', '100500100');
        $linkId = $offer->retailer?->affiliate_program_id ?? $config['default_link_id'] ?? '15500200';

        $sid = $customSubId ? preg_replace('/[^a-zA-Z0-9_-]/', '', substr($customSubId, 0, 50)) : 'arikartech';
        $encodedTarget = urlencode($targetUrl);

        return "https://www.anrdoezrs.net/click-{$websiteId}-{$linkId}?sid={$sid}&url={$encodedTarget}";
    }

    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        $provider = AffiliateProvider::where('code', 'cj')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        return [];
    }

    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        $provider = AffiliateProvider::where('code', 'cj')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        $config = $provider->config ?? [];
        $apiToken = $config['api_token'] ?? config('services.cj.api_token');
        $companyId = $config['company_id'] ?? config('services.cj.company_id');

        $graphql = '
            query searchProducts($companyId: ID!, $keywords: [String!], $limit: Int!) {
                products(companyId: $companyId, keywords: $keywords, limit: $limit) {
                    resultList {
                        id
                        title
                        description
                        price {
                            amount
                            currency
                        }
                        advertiserName
                        advertiserId
                        linkCode(pid: "default") {
                            clickUrl
                        }
                        targetUrl
                        imageLink
                        upc
                        isbn
                        gtin
                        manufacturerSku
                        brand
                        inStock
                    }
                }
            }
        ';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type' => 'application/json',
            ])->timeout(8)->post('https://ads.api.cj.com/query', [
                'query' => $graphql,
                'variables' => [
                    'companyId' => $companyId,
                    'keywords' => [$keywords],
                    'limit' => min($limit, 50),
                ],
            ]);

            if (!$response->successful()) {
                return [];
            }

            $items = $response->json()['data']['products']['resultList'] ?? [];
            $results = [];
            foreach ($items as $item) {
                $dto = $this->normalizeCjItem($item, $market);
                if ($dto) {
                    $results[] = $dto;
                }
            }

            return $results;
        } catch (Throwable $e) {
            Log::error("CJ product search error: {$e->getMessage()}");
            return [];
        }
    }

    public function normalizeCjItem(array $item, Market $market): ?NormalizedProductDTO
    {
        $id = $item['id'] ?? null;
        $title = $item['title'] ?? null;

        if (!$title || !$id) {
            return null;
        }

        $brandName = $item['brand'] ?? $item['advertiserName'] ?? 'Generic';
        $mpn = $item['manufacturerSku'] ?? null;
        $upc = $item['upc'] ?? null;
        $gtin = $item['gtin'] ?? null;
        $sku = (string) $id;

        $identifiers = [];
        if ($upc) {
            $identifiers[] = NormalizedIdentifierDTO::from('UPC', (string) $upc);
        }
        if ($gtin) {
            $identifiers[] = NormalizedIdentifierDTO::from('GTIN', (string) $gtin);
        }
        if ($mpn) {
            $identifiers[] = NormalizedIdentifierDTO::from('MPN', (string) $mpn);
        }
        $identifiers[] = NormalizedIdentifierDTO::from('SKU', $sku);

        // Price & Offer
        $price = isset($item['price']['amount']) ? (float) $item['price']['amount'] : 0.0;
        $currency = $item['price']['currency'] ?? $this->marketCurrencies[$market->code] ?? 'USD';
        $merchantName = $item['advertiserName'] ?? 'CJ Merchant';
        $merchantDomain = strtolower(str_replace(' ', '', $merchantName)) . '.com';
        $affiliateUrl = $item['linkCode']['clickUrl'] ?? $item['targetUrl'] ?? '';

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $merchantDomain,
            retailerName: $merchantName,
            sku: $sku,
            title: $title,
            price: $price,
            originalPrice: null,
            currencyCode: strtoupper($currency),
            availability: ($item['inStock'] ?? true) ? 'in_stock' : 'out_of_stock',
            condition: 'new',
            affiliateUrl: $affiliateUrl,
            originalUrl: $item['targetUrl'] ?? $affiliateUrl,
            shippingCost: 0.0,
            marketCode: $market->code
        );

        $images = [];
        if (!empty($item['imageLink'])) {
            $images[] = new NormalizedImageDTO($item['imageLink'], $title, true, 0);
        }

        return new NormalizedProductDTO(
            name: $title,
            brandName: $brandName,
            categorySlug: null,
            modelNumber: $mpn,
            description: $item['description'] ?? null,
            shortDescription: substr($title, 0, 250),
            canonicalUpc: $upc ? (string) $upc : null,
            canonicalEan: null,
            canonicalMpn: $mpn ? (string) $mpn : null,
            identifiers: $identifiers,
            specifications: [],
            images: $images,
            offer: $offerDto,
            providerCode: 'cj',
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
        return ['us', 'uk', 'de', 'fr', 'es', 'it', 'nl', 'au', 'nz'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'GBP', 'EUR', 'AUD', 'NZD'];
    }

    public function getSupportedCategories(): array
    {
        return ['Computers', 'Laptops', 'PC Components', 'Monitors', 'Peripherals', 'Smartphones', 'Audio'];
    }
}
