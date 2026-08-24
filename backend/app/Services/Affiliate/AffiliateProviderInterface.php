<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;

interface AffiliateProviderInterface
{
    /**
     * Unique code of the provider (e.g., 'awin', 'cj', 'impact', 'amazon', 'tradedoubler', 'rakuten')
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
     * Test live connection with currently configured credentials
     *
     * @return array{
     *   connected: bool,
     *   status: string, // 'connected', 'not_configured', 'invalid_credentials', 'rate_limited', 'error', 'deferred'
     *   message: string,
     *   latency_ms: ?int
     * }
     */
    public function testConnection(AffiliateProvider $provider): array;

    /**
     * Capability Detection: Does the provider support the given market?
     */
    public function supportsMarket(Market|string $market): bool;

    /**
     * Capability Detection: Does the provider support the given currency?
     */
    public function supportsCurrency(Currency|string $currency): bool;

    /**
     * Capability Detection: Does the provider support scheduled bulk product feeds?
     */
    public function supportsProductFeed(): bool;

    /**
     * Capability Detection: Does the provider support real-time lookup/search APIs?
     */
    public function supportsApi(): bool;

    /**
     * Capability Detection: Does the provider support deep link generation?
     */
    public function supportsDeepLinks(): bool;

    /**
     * Generate monetized affiliate deep-link with appropriate market tracking tag and sub-id
     */
    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string;

    /**
     * Lookup a single canonical product by identifier (UPC, EAN, ASIN, MPN)
     */
    public function getProduct(string $identifierType, string $identifierValue, Market $market): ?NormalizedProductDTO;

    /**
     * Batch lookup products by identifiers
     *
     * @return NormalizedProductDTO[]
     */
    public function getProducts(array $identifiers, Market $market): array;

    /**
     * Fetch product offers for a given identifier in a specific market
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
     * Alias for fetchProductOffers
     */
    public function getOffers(string $identifierType, string $identifierValue, Market $market): array;

    /**
     * Search products on provider network
     *
     * @return NormalizedProductDTO[]
     */
    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array;

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
     * Rate limit (requests per minute)
     */
    public function getRateLimit(): int;

    /**
     * Supported ISO market codes
     * @return string[]
     */
    public function getSupportedMarkets(): array;

    /**
     * Supported ISO-4217 currency codes
     * @return string[]
     */
    public function getSupportedCurrencies(): array;

    /**
     * Supported technology categories
     * @return string[]
     */
    public function getSupportedCategories(): array;
}
