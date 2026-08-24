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

class AmazonProvider extends BaseAffiliateProvider
{
    protected AmazonSigV4Signer $signer;

    /**
     * Regional Amazon PA-API 5.0 host mappings
     */
    protected array $marketHosts = [
        'us' => ['host' => 'webservices.amazon.com', 'region' => 'us-east-1', 'domain' => 'amazon.com'],
        'uk' => ['host' => 'webservices.amazon.co.uk', 'region' => 'eu-west-1', 'domain' => 'amazon.co.uk'],
        'de' => ['host' => 'webservices.amazon.de', 'region' => 'eu-west-1', 'domain' => 'amazon.de'],
        'fr' => ['host' => 'webservices.amazon.fr', 'region' => 'eu-west-1', 'domain' => 'amazon.fr'],
        'es' => ['host' => 'webservices.amazon.es', 'region' => 'eu-west-1', 'domain' => 'amazon.es'],
        'it' => ['host' => 'webservices.amazon.it', 'region' => 'eu-west-1', 'domain' => 'amazon.it'],
        'au' => ['host' => 'webservices.amazon.com.au', 'region' => 'us-west-2', 'domain' => 'amazon.com.au'],
    ];

    public function __construct(?AmazonSigV4Signer $signer = null)
    {
        $this->signer = $signer ?? new AmazonSigV4Signer();
    }

    public function getCode(): string
    {
        return 'amazon';
    }

    public function getName(): string
    {
        return 'Amazon Associates & PA-API 5.0';
    }

    /**
     * Check whether real API credentials are configured
     */
    public function isConnected(AffiliateProvider $provider): bool
    {
        $config = $provider->config ?? [];
        $accessKey = $config['access_key'] ?? config('services.amazon.paapi_key');
        $secretKey = $config['secret_key'] ?? config('services.amazon.paapi_secret');

        return !empty($accessKey) && !empty($secretKey);
    }

    /**
     * Test live API connection with currently configured credentials
     */
    public function testConnection(AffiliateProvider $provider): array
    {
        $startTime = microtime(true);

        if (!$this->isConnected($provider)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Amazon PA-API credentials (access key / secret key) are not configured.',
                'latency_ms' => null,
            ];
        }

        $config = $provider->config ?? [];
        $accessKey = $config['access_key'] ?? config('services.amazon.paapi_key');
        $secretKey = $config['secret_key'] ?? config('services.amazon.paapi_secret');

        // Test with standard GetItems call on a known ASIN
        $marketCode = 'us';
        $endpoint = $this->marketHosts[$marketCode] ?? $this->marketHosts['us'];
        $tag = $this->getAssociateTag($provider, $marketCode);

        $payload = json_encode([
            'ItemIds' => ['B0CX23V2ZP'], // Standard test reference ASIN
            'PartnerTag' => $tag ?: 'arikartech-20',
            'PartnerType' => 'Associates',
            'Marketplace' => 'www.amazon.com',
            'Resources' => ['ItemInfo.Title'],
        ]);

        $headers = $this->signer->sign(
            $accessKey,
            $secretKey,
            $endpoint['region'],
            $endpoint['host'],
            'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems',
            $payload
        );

        try {
            $response = Http::withHeaders($headers)
                ->timeout(5)
                ->post("https://{$endpoint['host']}/paapi5/getitems", json_decode($payload, true));

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'Successfully connected to Amazon PA-API 5.0.',
                    'latency_ms' => $latency,
                ];
            }

            if ($response->status() === 429) {
                return [
                    'connected' => false,
                    'status' => 'rate_limited',
                    'message' => 'Amazon PA-API rate limit exceeded. Please wait before retrying.',
                    'latency_ms' => $latency,
                ];
            }

            $errorData = $response->json();
            $errorMsg = $errorData['Errors'][0]['Message'] ?? "HTTP error {$response->status()}";

            return [
                'connected' => false,
                'status' => 'error',
                'message' => "Amazon PA-API returned: {$errorMsg}",
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

    /**
     * Generate monetized affiliate deep link with market tracking tag
     */
    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string
    {
        $url = $offer->original_url ?: $offer->affiliate_url;
        if (empty($url)) {
            return '';
        }

        $tag = $this->resolveMarketTag($offer, $market);
        if (empty($tag)) {
            return $url;
        }

        $parsed = parse_url($url);
        $queryParams = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        $queryParams['tag'] = $tag;
        if ($customSubId) {
            $queryParams['ascsubtag'] = substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $customSubId), 0, 50);
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? 'amazon.com';
        $path = $parsed['path'] ?? '/';
        $query = http_build_query($queryParams);

        return "{$scheme}://{$host}{$path}?{$query}";
    }

    /**
     * Fetch product offers for an identifier
     */
    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        $provider = AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        if ($identifierType === 'ASIN') {
            $products = $this->getItems([$identifierValue], $market, $provider);
            if (!empty($products) && $products[0]->offer) {
                $offer = $products[0]->offer;
                return [
                    [
                        'sku' => $offer->sku,
                        'title' => $offer->title,
                        'url' => $offer->affiliateUrl,
                        'price' => $offer->price,
                        'original_price' => $offer->originalPrice,
                        'currency_code' => $offer->currencyCode,
                        'availability' => $offer->availability,
                        'condition' => $offer->condition,
                    ],
                ];
            }
        }

        return [];
    }

    /**
     * Fetch full item details for an array of ASINs via PA-API 5.0 GetItems
     *
     * @param string[] $asins
     * @return NormalizedProductDTO[]
     */
    public function getItems(array $asins, Market $market, ?AffiliateProvider $provider = null): array
    {
        $provider = $provider ?? AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider || !$this->isConnected($provider) || empty($asins)) {
            return [];
        }

        $config = $provider->config ?? [];
        $accessKey = $config['access_key'] ?? config('services.amazon.paapi_key');
        $secretKey = $config['secret_key'] ?? config('services.amazon.paapi_secret');

        $marketCode = strtolower($market->code);
        $endpoint = $this->marketHosts[$marketCode] ?? $this->marketHosts['us'];
        $tag = $this->getAssociateTag($provider, $marketCode);

        $payload = json_encode([
            'ItemIds' => array_values(array_slice($asins, 0, 10)),
            'PartnerTag' => $tag ?: 'arikartech-20',
            'PartnerType' => 'Associates',
            'Marketplace' => "www.{$endpoint['domain']}",
            'Resources' => [
                'ItemInfo.Title',
                'ItemInfo.ByLineInfo',
                'ItemInfo.Classifications',
                'ItemInfo.Features',
                'ItemInfo.ProductInfo',
                'ItemInfo.TechnicalInfo',
                'Images.Primary.Large',
                'Images.Variants.Large',
                'Offers.Listings.Price',
                'Offers.Listings.Availability.Type',
                'Offers.Listings.Condition',
                'Offers.Listings.SavingBasis',
            ],
        ]);

        $headers = $this->signer->sign(
            $accessKey,
            $secretKey,
            $endpoint['region'],
            $endpoint['host'],
            'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems',
            $payload
        );

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post("https://{$endpoint['host']}/paapi5/getitems", json_decode($payload, true));

            if (!$response->successful()) {
                Log::warning("Amazon PA-API GetItems failed: HTTP {$response->status()}");
                return [];
            }

            $data = $response->json();
            $items = $data['ItemsResult']['Items'] ?? [];

            $normalizedProducts = [];
            foreach ($items as $rawItem) {
                $normalized = $this->normalizePaapiItem($rawItem, $marketCode, $endpoint['domain']);
                if ($normalized) {
                    $normalizedProducts[] = $normalized;
                }
            }

            return $normalizedProducts;
        } catch (Throwable $e) {
            Log::error("Amazon PA-API request error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Search items on Amazon via PA-API 5.0 SearchItems
     *
     * @return NormalizedProductDTO[]
     */
    public function searchItems(string $keywords, Market $market, ?string $category = null, int $itemCount = 10): array
    {
        $provider = AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        $config = $provider->config ?? [];
        $accessKey = $config['access_key'] ?? config('services.amazon.paapi_key');
        $secretKey = $config['secret_key'] ?? config('services.amazon.paapi_secret');

        $marketCode = strtolower($market->code);
        $endpoint = $this->marketHosts[$marketCode] ?? $this->marketHosts['us'];
        $tag = $this->getAssociateTag($provider, $marketCode);

        $payload = json_encode([
            'Keywords' => $keywords,
            'ItemCount' => min($itemCount, 10),
            'SearchIndex' => $category ?? 'Electronics',
            'PartnerTag' => $tag ?: 'arikartech-20',
            'PartnerType' => 'Associates',
            'Marketplace' => "www.{$endpoint['domain']}",
            'Resources' => [
                'ItemInfo.Title',
                'ItemInfo.ByLineInfo',
                'ItemInfo.Classifications',
                'ItemInfo.Features',
                'ItemInfo.ProductInfo',
                'Images.Primary.Large',
                'Offers.Listings.Price',
                'Offers.Listings.Availability.Type',
                'Offers.Listings.Condition',
            ],
        ]);

        $headers = $this->signer->sign(
            $accessKey,
            $secretKey,
            $endpoint['region'],
            $endpoint['host'],
            'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems',
            $payload
        );

        try {
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post("https://{$endpoint['host']}/paapi5/searchitems", json_decode($payload, true));

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $items = $data['SearchResult']['Items'] ?? [];

            $normalizedProducts = [];
            foreach ($items as $rawItem) {
                $normalized = $this->normalizePaapiItem($rawItem, $marketCode, $endpoint['domain']);
                if ($normalized) {
                    $normalizedProducts[] = $normalized;
                }
            }

            return $normalizedProducts;
        } catch (Throwable $e) {
            Log::error("Amazon SearchItems error: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Transform a single PA-API 5.0 raw item response into a NormalizedProductDTO
     */
    public function normalizePaapiItem(array $item, string $marketCode, string $domain = 'amazon.com'): ?NormalizedProductDTO
    {
        $asin = $item['ASIN'] ?? null;
        $itemInfo = $item['ItemInfo'] ?? [];
        $title = $itemInfo['Title']['DisplayValue'] ?? null;

        if (!$asin || !$title) {
            return null;
        }

        $brandName = $itemInfo['ByLineInfo']['Brand']['DisplayValue'] 
            ?? $itemInfo['ByLineInfo']['Manufacturer']['DisplayValue'] 
            ?? 'Generic';

        $modelNumber = $itemInfo['TechnicalInfo']['ModelNumber']['DisplayValue'] 
            ?? $itemInfo['ProductInfo']['ItemModelNumber']['DisplayValue'] 
            ?? null;

        $mpn = $itemInfo['ProductInfo']['PartNumber']['DisplayValue'] ?? null;
        $ean = $itemInfo['ProductInfo']['EANList']['DisplayValues'][0] ?? null;
        $upc = $itemInfo['ProductInfo']['UPCList']['DisplayValues'][0] ?? null;

        // Identifiers list
        $identifiers = [
            NormalizedIdentifierDTO::from('ASIN', $asin),
        ];
        if ($ean) {
            $identifiers[] = NormalizedIdentifierDTO::from('EAN', $ean);
        }
        if ($upc) {
            $identifiers[] = NormalizedIdentifierDTO::from('UPC', $upc);
        }
        if ($mpn) {
            $identifiers[] = NormalizedIdentifierDTO::from('MPN', $mpn);
        }

        // Specifications
        $specs = [];
        if (!empty($itemInfo['Features']['DisplayValues'])) {
            foreach ($itemInfo['Features']['DisplayValues'] as $idx => $feature) {
                $specs[] = new NormalizedSpecificationDTO('Key Features', "Feature " . ($idx + 1), trim($feature), $idx);
            }
        }

        // Images
        $images = [];
        $primaryImgUrl = $item['Images']['Primary']['Large']['URL'] ?? null;
        if ($primaryImgUrl) {
            $images[] = new NormalizedImageDTO(
                $primaryImgUrl,
                $title,
                true,
                0,
                $item['Images']['Primary']['Large']['Width'] ?? null,
                $item['Images']['Primary']['Large']['Height'] ?? null
            );
        }

        // Offer
        $offerDto = null;
        $listing = $item['Offers']['Listings'][0] ?? null;
        if ($listing && isset($listing['Price']['Amount'])) {
            $price = (float) $listing['Price']['Amount'];
            $currencyCode = $listing['Price']['Currency'] ?? 'USD';
            $savingBasis = $listing['SavingBasis']['Amount'] ?? null;
            $originalPrice = $savingBasis ? (float) $savingBasis : null;

            $availabilityRaw = $listing['Availability']['Type'] ?? 'Now';
            $availability = $availabilityRaw === 'Now' ? 'in_stock' : 'out_of_stock';

            $conditionRaw = $listing['Condition']['Value'] ?? 'New';
            $condition = strtolower($conditionRaw) === 'new' ? 'new' : 'refurbished';

            $detailPageUrl = $item['DetailPageURL'] ?? "https://www.{$domain}/dp/{$asin}";

            $offerDto = new NormalizedOfferDTO(
                retailerDomain: $domain,
                retailerName: 'Amazon',
                sku: $asin,
                title: $title,
                price: $price,
                originalPrice: $originalPrice,
                currencyCode: $currencyCode,
                availability: $availability,
                condition: $condition,
                affiliateUrl: $detailPageUrl,
                originalUrl: $detailPageUrl,
                shippingCost: 0.0,
                marketCode: $marketCode
            );
        }

        return new NormalizedProductDTO(
            name: $title,
            brandName: $brandName,
            categorySlug: null,
            modelNumber: $modelNumber,
            description: implode("\n", $itemInfo['Features']['DisplayValues'] ?? []),
            shortDescription: substr($title, 0, 250),
            canonicalUpc: $upc,
            canonicalEan: $ean,
            canonicalMpn: $mpn,
            identifiers: $identifiers,
            specifications: $specs,
            images: $images,
            offer: $offerDto,
            providerCode: 'amazon',
            externalId: $asin
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

    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        return $this->searchItems($keywords, $market, $category, $limit);
    }

    public function getSupportedMarkets(): array
    {
        return ['us', 'uk', 'de', 'fr', 'es', 'it', 'au'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'GBP', 'EUR', 'AUD'];
    }

    public function getSupportedCategories(): array
    {
        return ['Electronics', 'Computers', 'PC Components', 'Smartphones', 'Networking'];
    }

    public function getRateLimit(): int
    {
        return 60; // 1 request per second
    }

    protected function resolveMarketTag(Offer $offer, Market $market): ?string
    {
        $account = $offer->retailer?->affiliateProvider?->accounts()
            ->where('market_id', $market->id)
            ->first();

        if ($account && !empty($account->account_tag)) {
            return $account->account_tag;
        }

        return config("services.amazon.tags.{$market->code}");
    }

    protected function getAssociateTag(AffiliateProvider $provider, string $marketCode): ?string
    {
        $account = $provider->accounts()
            ->whereHas('market', function ($q) use ($marketCode) {
                $q->where('code', $marketCode);
            })->first();

        return $account?->account_tag ?? config("services.amazon.tags.{$marketCode}");
    }
}
