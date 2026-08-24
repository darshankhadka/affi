<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;

class TradeDoublerProvider extends BaseAffiliateProvider
{
    public function getCode(): string
    {
        return 'tradedoubler';
    }

    public function getName(): string
    {
        return 'TradeDoubler';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $token = config('services.tradedoubler.token', env('TRADEDOUBLER_TOKEN'));
        return !empty($token);
    }

    public function testConnection(AffiliateProvider $provider): array
    {
        $token = config('services.tradedoubler.token', env('TRADEDOUBLER_TOKEN'));
        if (empty($token)) {
            return [
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'TradeDoubler API token is not configured in .env',
                'latency_ms' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::timeout(5)
                ->withToken($token)
                ->get('https://api.tradedoubler.com/1.0/user');

            $latency = (int) ((microtime(true) - $start) * 1000);
            if ($response->successful()) {
                return [
                    'connected' => true,
                    'status' => 'connected',
                    'message' => 'TradeDoubler API connection verified',
                    'latency_ms' => $latency,
                ];
            }

            return [
                'connected' => false,
                'status' => 'error',
                'message' => "TradeDoubler returned HTTP {$response->status()}",
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
        $affiliateId = config('services.tradedoubler.affiliate_id', env('TRADEDOUBLER_AFFILIATE_ID', ''));
        
        if (empty($affiliateId) || empty($targetUrl)) {
            return $targetUrl ?: '/api/v1/affiliates/out/' . $offer->id;
        }

        $encodedUrl = urlencode($targetUrl);
        $subId = $customSubId ? '&epi=' . urlencode($customSubId) : '';
        return "https://clk.tradedoubler.com/click?p=0&a={$affiliateId}&url={$encodedUrl}{$subId}";
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
        return ['se', 'dk', 'no', 'fi', 'de', 'fr', 'gb', 'es', 'it', 'pl', 'nl', 'at', 'ch'];
    }

    public function getSupportedCurrencies(): array
    {
        return ['SEK', 'DKK', 'NOK', 'EUR', 'GBP', 'PLN', 'CHF'];
    }
}
