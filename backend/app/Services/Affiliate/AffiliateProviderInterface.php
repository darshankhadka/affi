<?php

namespace App\Services\Affiliate;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;

interface AffiliateProviderInterface
{
    /**
     * Unique code of the provider (e.g., 'amazon', 'awin', 'cj', 'impact', 'direct')
     */
    public function getCode(): string;

    /**
     * Human readable name
     */
    public function getName(): string;

    /**
     * Check whether real API credentials are configured and valid
     */
    public function isConnected(AffiliateProvider $provider): bool;

    /**
     * Generate monetized affiliate deep-link with appropriate market tracking tag and sub-id
     */
    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string;

    /**
     * Fetch product offers for a given identifier (UPC, EAN, ASIN, MPN) in a specific market
     * Returns empty array if disconnected or no offers found
     *
     * @return array<int, array{
     *   sku: string,
     *   title: string,
     *   url: string,
     *   price: float,
     *   original_price: ?float,
     *   currency_code: string,
     *   availability: string,
     *   condition: string
     * }>
     */
    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array;

    /**
     * Sync catalog batch in a bounded, resumable manner
     *
     * @return array{
     *   processed: int,
     *   items: array,
     *   next_cursor: ?string,
     *   has_more: bool
     * }
     */
    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array;

    /**
     * Test live API connection with currently configured credentials
     *
     * @return array{
     *   connected: bool,
     *   status: string, // 'connected', 'not_configured', 'invalid_credentials', 'rate_limited', 'error'
     *   message: string,
     *   latency_ms: ?int
     * }
     */
    public function testConnection(AffiliateProvider $provider): array;

    /**
     * Rate limit (requests per minute)
     */
    public function getRateLimit(): int;
}
