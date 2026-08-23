# ARIKARTECH Affiliate Engine Architecture (Phase 2)

## 1. Provider Abstraction & Data Boundary
ARIKARTECH maintains a strict boundary between provider-specific API payloads and the canonical domain catalog:

```
Provider API / Datafeed (Amazon PA-API 5.0)
            ↓
RawProductDTO / Raw Provider Payload
            ↓
ProductNormalizer (Deterministic Title, Brand, Model, & Identifier formatting)
            ↓
NormalizedProductDTO
            ↓
ProductMatchingService (O(1) Indexed Identifier Lookup: GTIN -> EAN -> UPC -> ASIN -> MPN)
            ↓
Canonical Product & Retailer Offer (Offers, PriceHistory, BestPrices)
```

---

## 2. Amazon PA-API 5.0 Connector Architecture

### A. AWS Signature Version 4 (SigV4) Signer
The `AmazonSigV4Signer` class creates HMAC-SHA256 authenticated headers for Amazon PA-API 5.0 endpoints across regional hosts:
- **US**: `webservices.amazon.com` (`us-east-1`)
- **UK**: `webservices.amazon.co.uk` (`eu-west-1`)
- **DE**: `webservices.amazon.de` (`eu-west-1`)
- **FR**: `webservices.amazon.fr` (`eu-west-1`)
- **ES**: `webservices.amazon.es` (`eu-west-1`)
- **IT**: `webservices.amazon.it` (`eu-west-1`)
- **AU**: `webservices.amazon.com.au` (`us-west-2`)

### B. Connection State & Live Testing
- `AmazonProvider::testConnection(AffiliateProvider $provider)` performs live credential verification without fabricating mock responses.
- If credentials are absent, it returns `status: not_configured` and live sync halts safely.
- When valid credentials exist, it performs a lightweight `GetItems` ping and records latency in milliseconds.

---

## 3. Real Ingestion & Duplicate Prevention Pipeline
1. `ProductIngestionService::ingest(NormalizedProductDTO $dto, ?Market $market)`
2. Normalizes brand aliases (e.g. "Apple Computer Inc." → "Apple") and strips trailing promotional marketing noise from retailer titles.
3. Checks unique `product_identifiers (type, normalized_value)` table.
4. **If matched**: Associates the new retailer offer to the existing canonical product and adds any new verified identifiers.
5. **If unmatched**: Creates a single canonical product entity with specifications, primary image, and identifiers.
6. **Offer creation & Pricing**:
   - Creates or updates `Offer` record.
   - Records `PriceHistory` only when price or stock availability shifts.
   - Materializes `BestPrice` index for sub-millisecond public lookups.

---

## 4. Outbound Click Flow & Anti-Fraud Privacy
1. Visitor clicks "View Deal" on any product page (`/api/out/{offerId}`).
2. Redirect route records `affiliate_clicks` with SHA256 hashed IP (`hash('sha256', $ip . config('app.key'))`).
3. Appends market-specific Associate tracking tag (`tag=...`) and session tracking sub-tag (`ascsubtag=...`).
4. Issues `302 Found` HTTP redirect with `X-Robots-Tag: noindex, nofollow`, `no-cache`, and `rel="nofollow sponsored"`.
