<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;

class RakutenProvider extends BaseAffiliateProvider
{
    public function getCode(): string
    {
        return 'rakuten';
    }

    public function getName(): string
    {
        return 'Rakuten Advertising';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $token = config('services.rakuten.token', env('RAKUTEN_API_TOKEN'));
        return !empty($token);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $token = config('services.rakuten.token', env('RAKUTEN_API_TOKEN'));
        if (empty($token)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'Rakuten API token is not configured in .env',
                'latency_ms' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::timeout(5)
                ->withToken($token)
                ->get('https://api.rakutenmarketing.com/coupon/1.0');

            $latency = (int) ((microtime(true) - $start) * 1000);
            if ($response->successful()) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'Rakuten Advertising API connection verified',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'connected' => false,
                'status' => 'error',
                'message' => "Rakuten API returned HTTP {$response->status()}",
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
        $ranMid = config('services.rakuten.mid', env('RAKUTEN_MID', ''));
        $ranSiteId = config('services.rakuten.site_id', env('RAKUTEN_SITE_ID', ''));

        if (empty($ranMid) || empty($ranSiteId) || empty($targetUrl)) {
            return $targetUrl ?: '/api/v1/affiliates/out/' . $offer->id;
        }

        $encodedUrl = urlencode($targetUrl);
        $u1 = $customSubId ? '&u1=' . urlencode($customSubId) : '';
        return "https://click.linksynergy.com/deeplink?id={$ranSiteId}&mid={$ranMid}&murl={$encodedUrl}{$u1}";
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
        return ['us', 'ca', 'gb', 'au', 'de', 'fr', 'es', 'it'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['USD', 'CAD', 'GBP', 'AUD', 'EUR'];
    }
}
