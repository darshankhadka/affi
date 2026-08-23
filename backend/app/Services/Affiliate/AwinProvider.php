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

class AwinProvider implements AffiliateProviderInterface
{
    /**
     * Regional Awin feed/API currency defaults
     */
    protected array $marketDefaults = [
        'uk' => ['currency' => 'GBP', 'domain' => 'awin1.com'],
        'de' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'fr' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'it' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'es' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'nl' => ['currency' => 'EUR', 'domain' => 'awin1.com'],
        'us' => ['currency' => 'USD', 'domain' => 'awin1.com'],
    ];

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
            ])->timeout(5)->get("https://api.awin.com/publishers/{$publisherId}/programmes", [
                'relationship' => 'joined',
            ]);

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'Successfully connected to Awin Publisher API.',
                    'latency_ms' => $latency,
                ];
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'connected' => false,
                    'status' => 'invalid_credentials',
                    'message' => 'Awin API returned unauthorized. Please verify API Token & Publisher ID.',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'connected' => false,
                'status' => 'error',
                'message' => "Awin API HTTP Error {$response->status()}: " . $response->body(),
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

        $advertiserId = $offer->retailer?->affiliate_program_id ?? $config['default_advertiser_id'] ?? '12345';
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

    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        $config = $provider->config ?? [];
        $apiKey = $config['api_token'] ?? config('services.awin.api_token');
        $publisherId = $config['publisher_id'] ?? config('services.awin.publisher_id');

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(8)->get("https://api.awin.com/publishers/{$publisherId}/productsearch", [
                'query' => $keywords,
                'limit' => min($limit, 50),
                'language' => $market->code === 'uk' ? 'en' : $market->code,
            ]);

            if (!$response->successful()) {
                return [];
            }

            $items = $response->json()['products'] ?? [];
            $results = [];
            foreach ($items as $item) {
                $dto = $this->normalizeAwinItem($item, $market);
                if ($dto) {
                    $results[] = $dto;
                }
            }

            return $results;
        } catch (Throwable $e) {
            Log::error("Awin search error: {$e->getMessage()}");
            return [];
        }
    }

    public function normalizeAwinItem(array $item, Market $market): ?NormalizedProductDTO
    {
        $id = $item['id'] ?? $item['product_id'] ?? null;
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
        $price = isset($item['price']) ? (float) $item['price'] : 0.0;
        $originalPrice = isset($item['retail_price']) ? (float) $item['retail_price'] : null;
        $currency = $item['currency'] ?? $this->marketDefaults[$market->code]['currency'] ?? 'EUR';
        $merchantName = $item['merchant_name'] ?? $item['advertiser_name'] ?? 'Awin Partner';
        $merchantDomain = $item['merchant_domain'] ?? strtolower(str_replace(' ', '', $merchantName)) . '.com';
        $productUrl = $item['deep_link'] ?? $item['aw_deep_link'] ?? $item['product_url'] ?? '';

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $merchantDomain,
            retailerName: $merchantName,
            sku: $sku,
            title: $title,
            price: $price,
            originalPrice: $originalPrice,
            currencyCode: strtoupper($currency),
            availability: ($item['in_stock'] ?? true) ? 'in_stock' : 'out_of_stock',
            condition: 'new',
            affiliateUrl: $productUrl,
            originalUrl: $item['merchant_deep_link'] ?? $productUrl,
            shippingCost: isset($item['delivery_cost']) ? (float) $item['delivery_cost'] : 0.0,
            marketCode: $market->code
        );

        $images = [];
        if (!empty($item['image_url'] ?? $item['aw_image_url'])) {
            $imgUrl = $item['image_url'] ?? $item['aw_image_url'];
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
        return 120; // 120 requests per minute
    }

    public function getSupportedMarkets(): array
    {
        return ['uk', 'de', 'fr', 'it', 'es', 'nl', 'us'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['GBP', 'EUR', 'USD'];
    }

    public function getSupportedCategories(): array
    {
        return ['Laptops', 'Smartphones', 'GPUs', 'CPUs', 'Monitors', 'Networking', 'Storage'];
    }
}
