# ARIKARTECH — Target Markets & Multi-Currency Engine

## 1. Supported Target Markets

| Market Code | Country Name | Primary Currency | Default Locale | Hreflang | Primary Affiliate Networks |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `us` | United States | USD (`$`) | `en-US` | `en-us` | CJ Affiliate, Impact, Amazon |
| `uk` | United Kingdom | GBP (`£`) | `en-GB` | `en-gb` | Awin, CJ Affiliate, Impact, Amazon |
| `de` | Germany | EUR (`€`) | `de-DE` | `de` | Awin, Impact, CJ Affiliate, Amazon |
| `fr` | France | EUR (`€`) | `fr-FR` | `fr` | Awin, CJ Affiliate, Amazon |
| `es` | Spain | EUR (`€`) | `es-ES` | `es` | Awin, CJ Affiliate, Amazon |
| `it` | Italy | EUR (`€`) | `it-IT` | `it` | Awin, CJ Affiliate, Amazon |
| `nl` | Netherlands | EUR (`€`) | `nl-NL` | `nl` | Awin, CJ Affiliate, Impact |
| `au` | Australia | AUD (`A$`) | `en-AU` | `en-au` | CJ Affiliate, Impact, Amazon |
| `nz` | New Zealand | NZD (`NZ$`) | `en-NZ` | `en-nz` | CJ Affiliate, Impact |

---

## 2. Market Isolation & URL Strategy
1. Every market possesses its own URL prefix: `/{market}/products/{slug}` (e.g. `/us/products/macbook-air-m3`, `/uk/products/macbook-air-m3`, `/de/products/macbook-air-m3`).
2. Pricing and stock availability are strictly isolated by `market_id`. A product price in USD is never displayed as EUR without explicit conversion and merchant offers in that market.
3. Markets with 0 active retailer offers for a given product are automatically set to `noindex, follow` via `SeoEligibilityService` to prevent search engine indexing of thin doorway pages.
4. Comprehensive multi-regional `hreflang` alternate links are automatically generated in HTML `<head>` and XML sitemaps.
