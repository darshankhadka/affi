# ARIKARTECH — Provider Architecture (Phase 3)

## 1. Overview & Multi-Network Philosophy
ARIKARTECH decouples the canonical catalog and public price-comparison engine from any single affiliate network. The platform supports multiple premier global networks while strictly preserving data integrity, truthful connection states, and zero fabricated records.

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        ARIKARTECH AFFILIATE REGISTRY                       │
└────────────────────────────────────────────────────────────────────────────┘
     │                    │                    │                   │
     ▼                    ▼                    ▼                   ▼
┌──────────┐        ┌──────────┐        ┌───────────┐       ┌──────────────┐
│   Awin   │        │    CJ    │        │  Impact   │       │Amazon PA-API │
│ (UK, EU) │        │(US, Global│       │ (Global)  │       │  (Deferred)  │
└──────────┘        └──────────┘        └───────────┘       └──────────────┘
     │                    │                    │                   │
     └────────────────────┼────────────────────┴───────────────────┘
                          │
                          ▼
            ┌───────────────────────────┐
            │  ProductNormalizer & DTOs │
            └───────────────────────────┘
                          │
                          ▼
            ┌───────────────────────────┐
            │   ProductMatchingService  │
            │ (O(1) Indexed Identifiers)│
            └───────────────────────────┘
                          │
                          ▼
            ┌───────────────────────────┐
            │   Canonical Ingestion &   │
            │   BestPrice Calculation   │
            └───────────────────────────┘
```

---

## 2. Selected Core Networks (4 Network Suite)

| Network | Primary Coverage | Authentication / Protocol | Top Merchant Categories | Driver Class |
| :--- | :--- | :--- | :--- | :--- |
| **Awin** | UK, DE, FR, IT, ES, NL | OAuth 2.0 / Bearer API Token + Publisher ID | Currys, MediaMarkt, Samsung, Dell, HP | `AwinProvider` |
| **CJ Affiliate** | US, UK, EU, AU, NZ | Personal Access Token (Bearer) + GraphQL / REST | Dell, Best Buy, Samsung, Lenovo, Newegg | `CjProvider` |
| **Impact.com** | US, UK, DE, AU, Global | HTTP Basic Auth (Account SID + Auth Token) | Lenovo, Razer, ASUS, Western Digital, B&H | `ImpactProvider` |
| **Amazon** | Global Marketplaces | AWS Signature Version 4 (PA-API 5.0) | Global catalog | `AmazonProvider` (Deferred) |

---

## 3. Unified Interface Specification
All affiliate network drivers implement [AffiliateProviderInterface](file:///media/arikar/laijau/affi/backend/app/Services/Affiliate/AffiliateProviderInterface.php):
- `getCode(): string`
- `getName(): string`
- `isConnected(AffiliateProvider $provider): bool`
- `testConnection(AffiliateProvider $provider): array`
- `generateAffiliateUrl(Offer $offer, Market $market, ?string $customSubId = null): string`
- `fetchProductOffers(string $identifierType, string $identifierValue, Market $market): array`
- `searchProducts(string $keywords, Market $market, ?string $category = null, int $limit = 20): array`
- `syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array`
- `getRateLimit(): int`
- `getSupportedMarkets(): array`
- `getSupportedCurrencies(): array`
- `getSupportedCategories(): array`
