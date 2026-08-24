<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;

class PartnerizeProvider extends BaseAffiliateProvider
{
    public function getCode(): string
    {
        return 'partnerize';
    }

    public function getName(): string
    {
        return 'Partnerize';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $userKey = config('services.partnerize.user_key', env('PARTNERIZE_USER_KEY'));
        return !empty($userKey);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $userKey = config('services.partnerize.user_key', env('PARTNERIZE_USER_KEY'));
        $apiKey = config('services.partnerize.api_key', env('PARTNERIZE_API_KEY'));

        if (empty($userKey) || empty($apiKey)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Partnerize API credentials are not configured in .env',
                'latency_ms' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::timeout(5)
                ->withBasicAuth($userKey, $apiKey)
                ->get('https://api.partnerize.com/user');

            $latency = (int) ((microtime(true) - $start) * 1000);
            if ($response->successful()) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'Partnerize API connection verified',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'connected' => false,
                'status' => 'error',
                'message' => "Partnerize API returned HTTP {$response->status()}",
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
                'latency_ms' => (int) ((microtime(true) - $start) * 1000),
            ];
        }
    }

    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string
    {
        $targetUrl = $offer->original_url ?: $offer->affiliate_url;
        $publisherId = config('services.partnerize.publisher_id', env('PARTNERIZE_PUBLISHER_ID', ''));
        $camref = config('services.partnerize.camref', env('PARTNERIZE_CAMREF', ''));

        if (empty($camref) || empty($targetUrl)) {
            return $targetUrl ?: '/api/v1/affiliates/out/' . $offer->id;
        }

        $destination = urlencode($targetUrl);
        $pubref = $customSubId ? '/pubref:' . urlencode($customSubId) : '';
        return "https://prf.hn/click/camref:{$camref}{$pubref}/destination:{$destination}";
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
        return ['gb', 'us', 'au', 'de', 'fr', 'nz', 'ca'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['GBP', 'USD', 'AUD', 'EUR', 'NZD', 'CAD'];
    }
}
