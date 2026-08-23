# ARIKARTECH Affiliate Engine Architecture

## 1. Provider Abstraction
ARIKARTECH decouples core catalog management and pricing models from specific affiliate networks using the `AffiliateProviderInterface`.

```php
interface AffiliateProviderInterface
{
    public function getCode(): string;
    public function getName(): string;
    public function isConnected(AffiliateProvider $provider): bool;
    public function generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string;
    public function fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array;
    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array;
    public function getRateLimit(): int;
}
```

---

## 2. Supported Networks & Drivers
- `Amazon Associates` (`AmazonProvider`): Supports Amazon PA-API 5.0 and direct deep linking across regional storefronts (US, UK, DE, FR, ES, IT, etc.) with store associate tags configured per market.
- `Awin / ShareASale` (Planned connector blueprint)
- `CJ Affiliate / Impact Radius` (Planned connector blueprint)
- `Direct Merchant Agreements` (`direct` driver)

---

## 3. Disconnected / Real Data Guarantee
- If API credentials or product feeds are unconfigured or unavailable, the connector explicitly reports `isConnected() === false`.
- The system **never fabricates or simulates** fake API responses, fake prices, or fake affiliate earnings.
- When disconnected, offers remain in an unverified/pending state and are refreshed safely when real credentials are provided.

---

## 4. Outbound Click Flow & Compliance
1. Visitor clicks "View Deal" on any product page.
2. Link routes to `/api/out/{offerId}` (or `/api/v1/affiliates/out/{offerId}`).
3. Backend logs click event in `affiliate_clicks` table:
   - `offer_id`, `product_id`, `retailer_id`, `market_id`
   - Privacy-safe SHA256 hashed IP address
   - Session ID and User-Agent
4. Provider connector injects market-specific affiliate tag (`tag=...`) and sub-tracking ID (`ascsubtag=...`).
5. Next.js / Laravel returns a `302 Found` redirect with headers:
   - `rel="nofollow sponsored"`
   - `X-Robots-Tag: noindex, nofollow`
   - `Cache-Control: no-cache, no-store, must-revalidate`
