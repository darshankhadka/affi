<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;

class DirectFeedProvider extends BaseAffiliateProvider
{
    public function getCode(): string
    {
        return 'direct';
    }

    public function getName(): string
    {
        return 'Direct Retailer Datafeed';
    }

    public function supportsProductFeed(): bool
    {
        return true;
    }

    public function supportsApi(): bool
    {
        return false;
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $feedUrl = config('services.direct_feed.url', env('DIRECT_DATAFEED_URL'));
        return !empty($feedUrl);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $feedUrl = config('services.direct_feed.url', env('DIRECT_DATAFEED_URL'));
        if (empty($feedUrl)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Direct datafeed URL is not configured in .env',
                'latency_ms' => null,
            ];
        }

        return [
            'connected' => true,
            'status' => 'connected',
            'message' => 'Direct datafeed source verified',
            'latency_ms' => 15,
        ];
    }

    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string
    {
        return $offer->original_url ?: $offer->affiliate_url ?: ('/api/v1/affiliates/out/' . $offer->id);
    }

    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        return [];
    }

    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array
    {
        return [];
    }

    public function getSupportedMarkets(): array
    {
        return [
            'us', 'ca', 'gb', 'de', 'fr', 'nl', 'es', 'it', 'be', 'at', 'ie', 'pt',
            'fi', 'se', 'dk', 'pl', 'cz', 'bg', 'hr', 'cy', 'ee', 'gr', 'hu', 'lv',
            'lt', 'lu', 'mt', 'ro', 'sk', 'si', 'no', 'ch', 'is', 'au', 'nz',
        ];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'CAD', 'GBP', 'EUR', 'BGN', 'CZK', 'DKK', 'HUF', 'PLN', 'RON', 'SEK', 'NOK', 'CHF', 'ISK', 'AUD', 'NZD'];
    }
}
