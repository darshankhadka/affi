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

class ImpactProvider extends BaseAffiliateProvider
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
        return 'impact';
    }

    public function getName(): string
    {
        return 'Impact (Impact.com)';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $config = $provider->config ?? [];
        $accountSid = $config['account_sid'] ?? config('services.impact.account_sid');
        $authToken = $config['auth_token'] ?? config('services.impact.auth_token');

        return !empty($accountSid) && !empty($authToken);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $startTime = microtime(true);

        if (!$this->isConnected($provider)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Impact credentials (Account SID / Auth Token) are not configured.',
                'latency_ms' => null,
            ];
        }

        $config = $provider->config ?? [];
        $accountSid = $config['account_sid'] ?? config('services.impact.account_sid');
        $authToken = $config['auth_token'] ?? config('services.impact.auth_token');

        try {
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(6)
                ->get("https://api.impact.com/Mediapartners/{$accountSid}/Campaigns");

            $latency = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'Successfully connected to Impact Mediapartners API.',
                    'latency_ms' => $latency,
                ];
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'connected' => false,
                    'status' => 'invalid_credentials',
                    'message' => 'Impact authentication failed. Please verify Account SID & Auth Token.',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'connected' => false,
                'status' => 'error',
                'message' => "Impact API error {$response->status()}: " . $response->body(),
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
        $mediaPartnerId = $config['media_partner_id'] ?? config('services.impact.media_partner_id', '2500100');
        $campaignId = $offer->retailer?->affiliate_program_id ?? $config['default_campaign_id'] ?? '12000';

        $subId1 = $customSubId ? preg_replace('/[^a-zA-Z0-9_-]/', '', substr($customSubId, 0, 50)) : 'arikartech';
        $encodedTarget = urlencode($targetUrl);

        return "https://impact.sjv.io/c/{$mediaPartnerId}/{$campaignId}/1234?subId1={$subId1}&u={$encodedTarget}";
    }

    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        $provider = AffiliateProvider::where('code', 'impact')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        return [];
    }

    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        $provider = AffiliateProvider::where('code', 'impact')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [];
        }

        $config = $provider->config ?? [];
        $accountSid = $config['account_sid'] ?? config('services.impact.account_sid');
        $authToken = $config['auth_token'] ?? config('services.impact.auth_token');

        try {
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(8)
                ->get("https://api.impact.com/Mediapartners/{$accountSid}/Catalogs/ItemSearch", [
                    'Query' => $keywords,
                    'PageSize' => min($limit, 50),
                ]);

            if (!$response->successful()) {
                return [];
            }

            $items = $response->json()['Items'] ?? [];
            $results = [];
            foreach ($items as $item) {
                $dto = $this->normalizeImpactItem($item, $market);
                if ($dto) {
                    $results[] = $dto;
                }
            }

            return $results;
        } catch (Throwable $e) {
            Log::error("Impact search error: {$e->getMessage()}");
            return [];
        }
    }

    public function normalizeImpactItem(array $item, Market $market): ?NormalizedProductDTO
    {
        $id = $item['Id'] ?? $item['CatalogItemId'] ?? null;
        $title = $item['Name'] ?? $item['ProductName'] ?? null;

        if (!$title || !$id) {
            return null;
        }

        $brandName = $item['Manufacturer'] ?? $item['CampaignName'] ?? 'Generic';
        $mpn = $item['ManufacturerPartNumber'] ?? $item['Mpn'] ?? null;
        $upc = $item['Upc'] ?? null;
        $ean = $item['Gtin'] ?? $item['Ean'] ?? null;
        $sku = (string) $id;

        $identifiers = [];
        if ($upc) {
            $identifiers[] = NormalizedIdentifierDTO::from('UPC', (string) $upc);
        }
        if ($ean) {
            $identifiers[] = NormalizedIdentifierDTO::from('EAN', (string) $ean);
        }
        if ($mpn) {
            $identifiers[] = NormalizedIdentifierDTO::from('MPN', (string) $mpn);
        }
        $identifiers[] = NormalizedIdentifierDTO::from('SKU', $sku);

        // Price & Offer
        $price = isset($item['CurrentPrice']) ? (float) $item['CurrentPrice'] : (float) ($item['Price'] ?? 0.0);
        $originalPrice = isset($item['OriginalPrice']) ? (float) $item['OriginalPrice'] : null;
        $currency = $item['Currency'] ?? $this->marketCurrencies[$market->code] ?? 'USD';
        $merchantName = $item['CampaignName'] ?? 'Impact Merchant';
        $merchantDomain = strtolower(str_replace(' ', '', $merchantName)) . '.com';
        $affiliateUrl = $item['Url'] ?? $item['TrackingLink'] ?? '';

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $merchantDomain,
            retailerName: $merchantName,
            sku: $sku,
            title: $title,
            price: $price,
            originalPrice: $originalPrice,
            currencyCode: strtoupper($currency),
            availability: ($item['StockAvailability'] ?? 'InStock') === 'InStock' ? 'in_stock' : 'out_of_stock',
            condition: 'new',
            affiliateUrl: $affiliateUrl,
            originalUrl: $item['OriginalUrl'] ?? $affiliateUrl,
            shippingCost: isset($item['ShippingRate']) ? (float) $item['ShippingRate'] : 0.0,
            marketCode: $market->code
        );

        $images = [];
        if (!empty($item['ImageUrl'] ?? $item['DefaultImageUrl'])) {
            $imgUrl = $item['ImageUrl'] ?? $item['DefaultImageUrl'];
            $images[] = new NormalizedImageDTO($imgUrl, $title, true, 0);
        }

        return new NormalizedProductDTO(
            name: $title,
            brandName: $brandName,
            categorySlug: null,
            modelNumber: $mpn,
            description: $item['Description'] ?? null,
            shortDescription: substr($title, 0, 250),
            canonicalUpc: $upc ? (string) $upc : null,
            canonicalEan: $ean ? (string) $ean : null,
            canonicalMpn: $mpn ? (string) $mpn : null,
            identifiers: $identifiers,
            specifications: [],
            images: $images,
            offer: $offerDto,
            providerCode: 'impact',
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
        return 90; // 90 requests per minute
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
        return ['Laptops', 'Gaming PCs', 'PC Components', 'Monitors', 'Storage', 'Smartphones', 'Peripherals'];
    }
}
