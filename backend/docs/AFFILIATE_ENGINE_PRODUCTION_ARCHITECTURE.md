# ARIKARTECH — Affiliate Engine Production Architecture

## 1. Executive Summary & Philosophy

ARIKARTECH is a global technology product-discovery and price-comparison platform powered purely by legitimate affiliate commissions.

The affiliate engine is architected around two core operational principles:
1. **Provider-Agnostic Core with Dedicated Network Adapters**: All affiliate networks (Awin, CJ Affiliate, Amazon Associates, Impact, etc.) feed into a unified ingestion, normalization, matching, and best-price materialization pipeline without forcing them into a false uniform abstraction.
2. **Explicit Partner & Programme Approval Enforcement**: A product's presence in an affiliate network feed or API does **never** equal approval to promote that partner. An offer is only promotable if the publisher has an approved programme relationship (`status === 'approved'`) with that advertiser/merchant.

```
   ┌────────────────┐      ┌─────────────────┐      ┌─────────────────────────┐
   │  Awin Feeds /  │      │   CJ Affiliate  │      │  Amazon (Mode 1 Manual  │
   │  API (Joined)  │      │ GraphQL(JOINED) │      │  / Mode 2 Dormant PA)   │
   └───────┬────────┘      └────────┬────────┘      └────────────┬────────────┘
           │                        │                            │
           ▼                        ▼                            ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                 Affiliate Provider Driver / Normalizer                   │
   │             (NormalizedProductDTO & NormalizedOfferDTO)                  │
   └────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │              Identifier-First Product Matching Service                   │
   │            (EAN → UPC → GTIN → ASIN → MPN → Brand+Model)                 │
   └────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                       Canonical Product Catalog                          │
   │            (Canonical Product 1 ─── N Retailer Offers)                   │
   └────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                Best-Price & Multi-Currency Materialization               │
   │          (Isolated per Market; In-Stock Prioritized; Snapshots)          │
   └────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │          Frontend Buy CTA ───► /go/{offerId} Outbound Redirect           │
   │          (Programme Approval Gate ──► Privacy Hash Click Telemetry       │
   │           ──► Provider Adapter Dynamic Tracking Tag Generation)          │
   └──────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Integrated Networks & Operational Modes

### A. Awin (Automated Feeds + API)
- **API Connectivity**: OAuth 2.0 / Bearer token via `https://api.awin.com/publishers/{publisherId}/programmes?relationship=joined`.
- **Feed Architectures**:
  - **Architecture A (Publisher-Wide Shared Feed)**: Single streaming download with on-the-fly GZIP decompression (`inflate_init`/`inflate_add`) and CSV row parsing. Each row's merchant ID and name are dynamically extracted.
  - **Architecture B (Advertiser-Specific /mid Feeds)**: Individual feeds for approved programmes via `AWIN_DATAFEED_API_KEY`.
- **Approved Programmes (Database-Seeded)**:
  1. `25962` — BlazeVideo DE (EUR)
  2. `8800` — mcdaekonline DK (DKK)
  3. `57897` — Geekbuying DE (EUR)
  4. `75408` — Nothingprojector (USD)
  5. `90211` — Fast Technology Limited (USD)

### B. CJ Affiliate (Commission Junction)
- **API Connectivity**: GraphQL API via `https://ads.api.cj.com/query` using Personal Access Token.
- **Approval Enforcement at API Layer**: Every query enforces `partnerStatus: JOINED` to ensure only joined advertisers are discoverable.
- **Pagination**: Zero-offset limit/offset pagination (avoids combining `sortBy` with `nextPage` which CJ rejects).
- **Tracking URL Generation**: `https://www.anrdoezrs.net/click-{websiteId}-{linkId}?sid={subid}&url={targetUrl}`.

### C. Amazon Associates
- **Mode 1 — Manual Amazon Affiliate Import (Active)**:
  - Admin pastes legitimate Amazon URL (e.g. `https://www.amazon.com/dp/B0CX23V2ZP`, `.co.uk`, `.de`, `.fr`, `.es`, `.it`, `.ca`, `.com.au`).
  - ASIN and marketplace domain are parsed with regex.
  - Market associate tag (`arikartech-20`, `arikartechuk-21`, `arikartechde-21`, etc.) is injected.
  - Ingested/matched against canonical product catalog, offer created, best price recalculated.
  - Strict adherence to Amazon terms: zero unauthorized HTML scraping.
- **Mode 2 — PA-API 5.0 (Dormant & Production-Ready)**:
  - AWS SigV4 request signer (`AmazonSigV4Signer.php`), PA-API GetItems/SearchItems.
  - Truthfully reports `not_configured` or `deferred` until API account eligibility and credentials are provided.

---

## 3. Product Ingestion & Matching Hierarchy

Every product ingested passes through deterministic identifier matching:
1. **GTIN / EAN / UPC** (Global barcode numbers, padded/normalized).
2. **ASIN** (Amazon Standard Identification Number).
3. **MPN** (Manufacturer Part Number) + Brand.
4. **Brand + Normalized Model Number**.

If no identifier matches, a new canonical product is created with a clean slug. Offers from multiple retailers (e.g. Amazon, BlazeVideo, Geekbuying) attach to the exact same canonical product, enabling clean side-by-side price comparison.

---

## 4. Multi-Market Isolation & Best Price Engine

- **35 Global Markets & 16 Currencies**: US, UK (`gb`), DE, FR, ES, IT, NL, BE, AT, IE, DK, SE, FI, NO, PL, CZ, PT, AU, NZ, CA, etc.
- **Currency Isolation**: Never compares USD and EUR directly. Best price is calculated strictly per `(product_id, market_id)`.
- **In-Stock Priority**: In-stock offers are prioritized over cheaper out-of-stock offers.
- **Price History**: Recorded exclusively when a legitimate price shift occurs (prevents noisy snapshot tables).

---

## 5. Security & Redirect Flow

Outbound redirects (`/go/{offerId}` or `/api/v1/affiliates/out/{offerId}`):
1. Verifies offer exists and `is_active === true`.
2. Verifies retailer's programme approval status (`AffiliateProgramme::isApproved()`). If a programme is rejected/pending/suspended, redirect is blocked with `403` and logged.
3. Generates a privacy-safe SHA-256 IP hash (no raw PII stored).
4. Records click event (`AffiliateClick`).
5. Generates destination deep-link via provider adapter with subid/clickref.
6. Returns HTTP 302 Found redirect with `X-Robots-Tag: noindex, nofollow` and `Referrer-Policy: no-referrer-when-downgrade`.

---

## 6. Shared Hosting & CPU Safety Constraints

- **No Daemons Required**: Operates via standard cron / Laravel Scheduler.
- **Atomic Mutex Locks**: Cache lock with deterministic TTL per provider/market prevents concurrency race conditions.
- **Bounded Micro-Batches**: Ingestion bounded to 25–50 items per run with strict memory/runtime caps.
- **Zero-Result Safety**: A provider returning 0 items never deactivates or deletes existing valid offers.
