<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmazonProvider implements AffiliateProviderInterface
{
    public function getCode(): string
    {
        return 'amazon';
    }

    public function getName(): string
    {
        return 'Amazon Associates';
    }

    public function isConnected(AffiliateProvider $provider): bool
    {
        $config = $provider->config ?? [];
        $accessKey = $config['access_key'] ?? config('services.amazon.access_key');
        $secretKey = $config['secret_key'] ?? config('services.amazon.secret_key');

        return !empty($accessKey) && !empty($secretKey);
    }

    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string
    {
        // Get market-specific Associate Tag
        $account = $offer->retailer?->affiliateProvider?->accounts()
            ->where('market_id', $market->id)
            ->where('is_active', true)
            ->first();

        $associateTag = $account?->account_tag 
            ?? config("services.amazon.tags.{$market->code}")
            ?? config('services.amazon.default_tag');

        $baseUrl = $offer->original_url ?: $offer->affiliate_url;
        if (empty($baseUrl)) {
            return '';
        }

        $parsedUrl = parse_url($baseUrl);
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        if ($associateTag) {
            $queryParams['tag'] = $associateTag;
        }

        if ($customSubId) {
            $queryParams['ascsubtag'] = $customSubId;
        }

        $scheme = $parsedUrl['scheme'] ?? 'https';
        $host = $parsedUrl['host'] ?? 'www.amazon.com';
        $path = $parsedUrl['path'] ?? '/';
        $query = http_build_query($queryParams);

        return "{$scheme}://{$host}{$path}" . ($query ? "?{$query}" : '');
    }

    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        $provider = AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider || !$this->isConnected($provider)) {
            Log::info("Amazon PA-API is not configured/connected. Skipping live fetch for {$identifierType}: {$identifierValue}");
            return [];
        }

        // Production PA-API 5.0 call would execute here with real credentials
        // When disconnected, return empty array without fabricated fake data
        return [];
    }

    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array
    {
        $provider = AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider || !$this->isConnected($provider)) {
            return [
                'processed' => 0,
                'items' => [],
                'next_cursor' => null,
                'has_more' => false,
            ];
        }

        return [
            'processed' => 0,
            'items' => [],
            'next_cursor' => null,
            'has_more' => false,
        ];
    }

    public function getRateLimit(): int
    {
        return 60; // 1 request per second for standard PA-API baseline
    }
}
