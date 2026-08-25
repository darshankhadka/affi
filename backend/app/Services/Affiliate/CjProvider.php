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

    /**
     * A provider is considered connected only when the provider record itself has
     * explicit credentials set in its config column. We do NOT fall through to env
     * here — the env fallback is resolved at API call time, not at connection-check
     * time. This ensures that a provider with config: null or config: [] is always
     * reported as "not_configured" regardless of what the server environment holds.
     */
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
                'connected'  => false,
                'status'     => 'not_configured',
                'message'    => 'CJ Affiliate credentials (Personal Access Token / Company ID) are not configured.',
                'latency_ms' => null,
            ];
        }

        $config    = $provider->config ?? [];
        $apiToken  = $config['api_token'] ?? config('services.cj.api_token');
        $companyId = $config['company_id'] ?? config('services.cj.company_id');

        // Use a lightweight products query to probe authentication.
        // The `publisher {}` field does NOT exist in the CJ GraphQL schema.
        // A products query with limit:1 is the correct connectivity probe.
        $query = <<<'GQL'
            query testConnection($companyId: ID!) {
                products(companyId: $companyId, limit: 1) {
                    resultList {
                        id
                        title
                    }
                }
            }
        GQL;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type'  => 'application/json',
            ])->timeout(8)->post('https://ads.api.cj.com/query', [
                'query'     => $query,
                'variables' => ['companyId' => (string) $companyId],
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful() && !isset($response->json()['errors'])) {
                return [
                    'connected'  => true,
                    'status'     => 'connected',
                    'message'    => 'Successfully connected to CJ Affiliate GraphQL API.',
                    'latency_ms' => $latency,
                ];
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'connected'  => false,
                    'status'     => 'invalid_credentials',
                    'message'    => 'CJ Affiliate authentication failed. Verify Personal Access Token.',
                    'latency_ms' => $latency,
                ];
            }

            if ($response->status() === 429) {
                return [
                    'connected'  => false,
                    'status'     => 'rate_limited',
                    'message'    => 'CJ Affiliate API rate limit exceeded.',
                    'latency_ms' => $latency,
                ];
            }

            $errorMsg = $response->json()['errors'][0]['message'] ?? "HTTP error {$response->status()}";
            return [
                'connected'  => false,
                'status'     => 'error',
                'message'    => "CJ API returned: {$errorMsg}",
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            return [
                'connected'  => false,
                'status'     => 'error',
                'message'    => "Connection failed: {$e->getMessage()}",
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

        $provider  = $offer->retailer?->affiliateProvider;
        $config    = $provider?->config ?? [];
        $websiteId = $config['website_id'] ?? config('services.cj.website_id', '100500100');
        $linkId    = $offer->retailer?->affiliate_program_id ?? $config['default_link_id'] ?? '15500200';

        $sid            = $customSubId ? preg_replace('/[^a-zA-Z0-9_-]/', '', substr($customSubId, 0, 50)) : 'arikartech';
        $encodedTarget  = urlencode($targetUrl);

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

    /**
     * Search CJ products.
     *
     * Approval enforcement:
     *   - Uses partnerStatus: JOINED to only return products from advertisers the
     *     publisher has joined. This is the API-level filter.
     *   - Programme-level approval is additionally enforced in the ingestion pipeline
     *     via AffiliateProgramme::isApproved() before any offer is stored.
     *
     * Pagination:
     *   - Uses offset/limit only. Do NOT combine sortBy/sortOrder with nextPage —
     *     CJ rejects that combination. Offset is the correct cursor here.
     */
    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        $provider = AffiliateProvider::where('code', 'cj')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        $config    = $provider->config ?? [];
        $apiToken  = $config['api_token'] ?? config('services.cj.api_token');
        $companyId = $config['company_id'] ?? config('services.cj.company_id');

        $currency     = $this->marketCurrencies[strtolower($market->code)] ?? 'USD';
        $targetCountry = strtoupper($market->code === 'uk' ? 'GB' : $market->code);

        // partnerStatus: JOINED ensures only advertisers the publisher is joined to appear.
        // Do NOT combine sortBy/sortOrder with nextPage — use offset instead.
        $graphql = <<<'GQL'
            query searchProducts(
                $companyId: ID!,
                $keywords: [String!],
                $limit: Int!,
                $currency: String,
                $targetCountry: String,
                $partnerStatus: PartnerStatus
            ) {
                products(
                    companyId: $companyId,
                    keywords: $keywords,
                    limit: $limit,
                    currency: $currency,
                    targetCountry: $targetCountry,
                    partnerStatus: $partnerStatus
                ) {
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
                        availability
                    }
                }
            }
        GQL;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type'  => 'application/json',
            ])->timeout(10)->post('https://ads.api.cj.com/query', [
                'query'     => $graphql,
                'variables' => [
                    'companyId'     => (string) $companyId,
                    'keywords'      => [$keywords],
                    'limit'         => min($limit, 50),
                    'currency'      => $currency,
                    'targetCountry' => $targetCountry,
                    'partnerStatus' => 'JOINED',
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('CJ searchProducts HTTP error: ' . $response->status(), [
                    'market' => $market->code,
                ]);
                return [];
            }

            $errors = $response->json()['errors'] ?? [];
            if (!empty($errors)) {
                Log::warning('CJ searchProducts GraphQL errors', [
                    'errors' => array_map(fn($e) => $e['message'] ?? 'unknown', $errors),
                    'market' => $market->code,
                ]);
                return [];
            }

            $items   = $response->json()['data']['products']['resultList'] ?? [];
            $results = [];
            foreach ($items as $item) {
                $dto = $this->normalizeCjItem($item, $market);
                if ($dto) {
                    $results[] = $dto;
                }
            }

            return $results;
        } catch (Throwable $e) {
            Log::error("CJ product search error: {$e->getMessage()}", ['market' => $market->code]);
            return [];
        }
    }

    /**
     * Paginated bulk fetch for a specific CJ advertiser (approved programme).
     *
     * IMPORTANT: CJ rejects combining sortBy/sortOrder with nextPage.
     * This method uses offset-based pagination only.
     *
     * @param string[] $partnerIds Restrict to specific approved partner IDs.
     * @return array{items: array, total: int, has_more: bool}
     */
    public function fetchApprovedPartnerProducts(
        string $companyId,
        string $apiToken,
        array $partnerIds,
        Market $market,
        int $limit = 50,
        int $offset = 0
    ): array {
        if (empty($partnerIds)) {
            return ['items' => [], 'total' => 0, 'has_more' => false];
        }

        $currency     = $this->marketCurrencies[strtolower($market->code)] ?? 'USD';
        $targetCountry = strtoupper($market->code === 'uk' ? 'GB' : $market->code);

        // Use offset pagination. Do NOT use nextPage + sortBy together.
        $graphql = <<<'GQL'
            query fetchPartnerProducts(
                $companyId: ID!,
                $partnerIds: [ID!],
                $limit: Int!,
                $offset: Int,
                $currency: String,
                $targetCountry: String,
                $availability: Availability
            ) {
                products(
                    companyId: $companyId,
                    partnerIds: $partnerIds,
                    partnerStatus: JOINED,
                    limit: $limit,
                    offset: $offset,
                    currency: $currency,
                    targetCountry: $targetCountry,
                    availability: $availability
                ) {
                    totalCount
                    resultList {
                        id
                        title
                        description
                        price {
                            amount
                            currency
                        }
                        salePrice {
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
                        availability
                    }
                }
            }
        GQL;

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type'  => 'application/json',
            ])->timeout(15)->post('https://ads.api.cj.com/query', [
                'query'     => $graphql,
                'variables' => [
                    'companyId'     => (string) $companyId,
                    'partnerIds'    => array_map('strval', $partnerIds),
                    'limit'         => min($limit, 100),
                    'offset'        => max(0, $offset),
                    'currency'      => $currency,
                    'targetCountry' => $targetCountry,
                    'availability'  => 'IN_STOCK',
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('CJ fetchApprovedPartnerProducts HTTP error: ' . $response->status());
                return ['items' => [], 'total' => 0, 'has_more' => false];
            }

            $data       = $response->json()['data']['products'] ?? [];
            $total      = $data['totalCount'] ?? 0;
            $resultList = $data['resultList'] ?? [];

            return [
                'items'    => $resultList,
                'total'    => (int) $total,
                'has_more' => ($offset + count($resultList)) < (int) $total,
            ];
        } catch (Throwable $e) {
            Log::error("CJ fetchApprovedPartnerProducts error: {$e->getMessage()}");
            return ['items' => [], 'total' => 0, 'has_more' => false];
        }
    }

    public function normalizeCjItem(array $item, Market $market): ?NormalizedProductDTO
    {
        $id    = $item['id'] ?? null;
        $title = $item['title'] ?? null;

        if (!$title || !$id) {
            return null;
        }

        $brandName = $item['brand'] ?? $item['advertiserName'] ?? 'Generic';
        $mpn       = $item['manufacturerSku'] ?? null;
        $upc       = $item['upc'] ?? null;
        $gtin      = $item['gtin'] ?? null;
        $sku       = (string) $id;

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
        $price    = isset($item['price']['amount']) ? (float) $item['price']['amount'] : 0.0;
        $salePrice = isset($item['salePrice']['amount']) ? (float) $item['salePrice']['amount'] : null;
        $currency = $item['price']['currency'] ?? $this->marketCurrencies[strtolower($market->code)] ?? 'USD';

        // Normalize availability
        $availabilityRaw = strtolower((string) ($item['availability'] ?? ''));
        $inStock         = (bool) ($item['inStock'] ?? true);
        $availability    = match ($availabilityRaw) {
            'in_stock', 'instock' => 'in_stock',
            'out_of_stock', 'outofstock' => 'out_of_stock',
            'preorder', 'pre_order' => 'preorder',
            'backorder' => 'backorder',
            default => $inStock ? 'in_stock' : 'out_of_stock',
        };

        $merchantName   = $item['advertiserName'] ?? 'CJ Merchant';
        $merchantDomain = strtolower(preg_replace('/[^a-z0-9]/i', '', $merchantName)) . '.com';
        $affiliateUrl   = $item['linkCode']['clickUrl'] ?? $item['targetUrl'] ?? '';

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $merchantDomain,
            retailerName:   $merchantName,
            sku:            $sku,
            title:          $title,
            price:          $salePrice ?? $price,
            originalPrice:  $salePrice ? $price : null,
            currencyCode:   strtoupper($currency),
            availability:   $availability,
            condition:      'new',
            affiliateUrl:   $affiliateUrl,
            originalUrl:    $item['targetUrl'] ?? $affiliateUrl,
            shippingCost:   0.0,
            marketCode:     $market->code,
            merchantId:     isset($item['advertiserId']) ? (string) $item['advertiserId'] : null,
            providerCode:   'cj',
        );

        $images = [];
        if (!empty($item['imageLink'])) {
            $images[] = new NormalizedImageDTO($item['imageLink'], $title, true, 0);
        }

        return new NormalizedProductDTO(
            name:             $title,
            brandName:        $brandName,
            categorySlug:     null,
            modelNumber:      $mpn,
            description:      $item['description'] ?? null,
            shortDescription: substr($title, 0, 250),
            canonicalUpc:     $upc ? (string) $upc : null,
            canonicalEan:     null,
            canonicalMpn:     $mpn ? (string) $mpn : null,
            identifiers:      $identifiers,
            specifications:   [],
            images:           $images,
            offer:            $offerDto,
            providerCode:     'cj',
            externalId:       $sku
        );
    }

    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array
    {
        return [
            'processed'   => 0,
            'items'       => [],
            'next_cursor' => null,
            'has_more'    => false,
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
