# ARIKARTECH — AFFILIATE PROVIDER ARCHITECTURE

## Overview
ARIKARTECH implements an extensible, capability-based affiliate provider driver system. The architecture decouples retailer metadata from network credentials, enabling a unified interface for direct retailer APIs, major affiliate networks, XML/CSV product datafeeds, and manual merchants.

---

## 1. Provider Capability Detection
Rather than forcing all providers to implement unsupported mechanisms, each provider declares capabilities via `AffiliateProviderInterface`:

```php
interface AffiliateProviderInterface
{
    public function getCode(): string;
    public function getName(): string;
    public function isConnected(AffiliateProvider $provider): bool;
    public function testConnection(AffiliateProvider $provider): array;
    public function supportsMarket(Market|string $market): bool;
    public function supportsCurrency(Currency|string $currency): bool;
    public function supportsProductFeed(): bool;
    public function supportsApi(): bool;
    public function supportsDeepLinks(): bool;
    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string;
    public function getProduct(string $identifierType, string $identifierValue, Market $market): ?NormalizedProductDTO;
    public function getProducts(array $identifiers, Market $market): array;
    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array;
    public function searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array;
    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array;
    public function getRateLimit(): int;
    public function getSupportedMarkets(): array;
    public function getSupportedCurrencies(): array;
    public function getSupportedCategories(): array;
}
```

---

## 2. Supported Network Providers

| Provider Code | Provider Name | Type | Key Capabilities | Auth Protocol |
| :--- | :--- | :--- | :--- | :--- |
| `amazon` | Amazon Associates / PA-API 5.0 | Direct API | Real-time Search, Lookup, Deep Linking | AWS SigV4 (HMAC-SHA256) |
| `awin` | Awin Publisher Network | Network & Feed | Streaming GZIP/CSV Datafeed, Merchant Lookup | OAuth Token / API Key |
| `cj` | CJ Affiliate (Commission Junction) | Network | GraphQL Product Search, Deep Linking | Personal Access Token |
| `impact` | Impact (Impact.com) | Network | REST API Lookup, Custom Deep Links | Basic Auth (SID + Token) |
| `tradedoubler` | TradeDoubler | Network | REST API Lookup, Deep Linking | Bearer Token |
| `rakuten` | Rakuten Advertising | Network | Coupon / Product API, LinkSynergy Links | Bearer Token / Web Services |
| `partnerize` | Partnerize | Network | Performance API, Prf.hn Deep Links | User Key + API Key |
| `direct` | Direct Retailer Datafeed | Feed | Bounded CSV/XML Streaming Ingestion | URL Datafeed / Token |

---

## 3. Credential Encryption at Rest
All account credentials, API secrets, and associate tokens are stored encrypted in MySQL using Laravel's `'encrypted'` model casts:

```php
protected $casts = [
    'credentials' => 'encrypted:array',
    'is_active' => 'boolean',
];
```

Secrets are never logged, never exposed via API resources, and never committed to Git.
