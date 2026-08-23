# ARIKARTECH — Europe + UK Affiliate Finalization Audit

**Date**: August 23, 2026  
**Auditor**: Senior Production Architect  
**Scope**: Europe & United Kingdom Affiliate Technology Discovery & Comparison Platform

---

## 1. Executive Summary & Strategy Shift

ARIKARTECH is transitioning its public commercial SEO targeting and catalog monetization exclusively to the **European Union / EEA and United Kingdom** markets:
- **Active Target Markets (15)**: Germany (DE), France (FR), Netherlands (NL), Spain (ES), Italy (IT), Belgium (BE), Austria (AT), Ireland (IE), Portugal (PT), Finland (FI), Sweden (SE), Denmark (DK), Poland (PL), Czech Republic (CZ), and United Kingdom (GB).
- **Excluded / Deactivated Non-Target Markets**: USA (US), Canada (CA), Australia (AU), New Zealand (NZ), Asia, Middle East, Africa, Latin America.
- **Data Preservation Rule**: Existing records for non-target markets (such as historical US/AU/NZ listings) are **NOT destructively deleted**. They are marked `is_active = false` and cleanly excluded from public XML sitemaps, robots indexing, and canonical discovery.

---

## 2. Component-by-Component Audit

### A. Database & Market Architecture
- **Currencies Required**:
  - `EUR` (€) — Euro (DE, FR, NL, ES, IT, BE, AT, IE, PT, FI, SE)
  - `GBP` (£) — British Pound (GB)
  - `DKK` (kr.) — Danish Krone (DK)
  - `PLN` (zł) — Polish Zloty (PL)
  - `CZK` (Kč) — Czech Koruna (CZ)
  - `USD`, `AUD`, `NZD` (Preserved in database for schema integrity and legacy isolation)
- **Market Codes**: 
  - Standard 2-letter ISO country codes used for clean URLs: `/de/`, `/fr/`, `/nl/`, `/es/`, `/it/`, `/be/`, `/at/`, `/ie/`, `/pt/`, `/fi/`, `/se/`, `/dk/`, `/pl/`, `/cz/`, `/gb/`.
  - Legacy `uk` alias mapped cleanly to `gb` to preserve existing backlinks and prevent 404s.

### B. Public Frontend & Static Export Architecture
- **Framework**: Next.js 15 Static HTML Export (`output: 'export'`, `trailingSlash: true`).
- **Build Independence**: 100% build-time independence from the live API achieved via `@/lib/catalog.ts` static canonical definitions.
- **Routing**: `generateStaticParams()` pre-renders all 15 active European + UK markets across homepages, 20 category hubs, 10 brand hubs, and static legal pages.
- **Client Dynamic Hydration**: Live search queries, side-by-side spec comparisons, dynamic offer refreshes, and outbound affiliate clicks execute client-side against `https://api.arikartech.com/api/v1/`.

### C. European SEO & Metadata Strategy
- **Hreflang Configuration**: Technically valid tags mapping active European markets:
  - `de-de`, `fr-fr`, `nl-nl`, `es-es`, `it-it`, `nl-be`, `de-at`, `en-ie`, `pt-pt`, `fi-fi`, `sv-se`, `da-dk`, `pl-pl`, `cs-cz`, `en-gb`, and `x-default` pointing to `/gb/` or `/de/`.
- **Structured Data**: Schema.org `Product`, `AggregateOffer`, `Offer`, `BreadcrumbList`, `WebSite`, and `Organization` JSON-LD.
- **Indexation Quality Gate**: Only canonical products with valid specs, image, and active retailer offers are indexable (`index, follow`). Incomplete products are marked `noindex, follow`.
- **Sitemaps**: Static XML sitemap pre-rendered for all 15 active target markets with zero non-target URLs.

### D. Affiliate Engine & Networks
- **Target Networks**: Awin, CJ Affiliate, Impact (all heavily dominant across EU & UK e-commerce).
- **Amazon Status**: Optional / deferred driver preserved. No scraping or fake data.
- **Outbound Redirection**: Secure `/api/v1/affiliates/out/{offerId}` with `rel="nofollow sponsored"`, 302 redirect, SHA-256 IP hashing, and no PII transmission.

### E. Analytics & Search Console
- **GA4**: Pre-configured with Measurement ID `G-XHPF26HB3N` tracking `page_view`, `view_item`, `search`, and `affiliate_click`.
- **GSC**: Pre-configured HTML verification meta tags preserved.

---

## 3. What Must Change vs What Must NOT Change

| Category | Must Change | Must NOT Change |
| :--- | :--- | :--- |
| **Markets** | Activate 15 EU + UK markets; deactivate US, AU, NZ in public SEO | Do not destructively drop database tables or records |
| **Currencies** | Add DKK, PLN, CZK to seeders and database | Do not remove USD/AUD/NZD currency records |
| **Next.js Export**| Pre-render 15 European markets in `generateStaticParams` | Keep `output: 'export'`, `unoptimized: true` |
| **Sitemap** | Generate sitemaps for 15 EU+UK markets | Never include search pages, compare routes, or 404s |
| **Hreflang** | Map exact language-country combinations (e.g. `de-de`, `da-dk`) | Do not invent non-existent language variants |
| **Admin SPA** | Allow managing all 15 EU/UK markets and their active status | Keep Vite/React architecture intact |
| **Automation** | Maintain strict CPU limits (<= 240s runtime, <= 100 items/run) | Do not introduce Redis, Supervisor, or long-running daemons |

---

## 4. Production Risks & Mitigations

1. **Risk: Static Build Timeout or RAM Overflow with 15 Markets**:
   - *Mitigation*: The static generator uses synchronous constant lookups and pre-renders only published shells and hubs. Total static pages = $15 \times 33 = 495$ pages, building in under 5 seconds.
2. **Risk: Legacy URL 404s from Prior Indexed US/UK Pages**:
   - *Mitigation*: Client redirect fallback and server `.htaccess` rules redirect legacy `/us/` traffic to default `/gb/` or `/de/`, and `/uk/` aliases cleanly to `/gb/`.
3. **Risk: Currency Mismatch on Best Prices**:
   - *Mitigation*: `BestPriceService` enforces strict `market_id` and matching `currency_id` isolation. Never compares GBP to EUR without explicit exchange rate conversion.
