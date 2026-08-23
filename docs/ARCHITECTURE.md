# ARIKARTECH Architecture Documentation

## 1. System Overview
ARIKARTECH is a high-performance, production-grade technology price-comparison and affiliate discovery platform. The business model is purely:
`SEO Organic Traffic → Product Discovery → Price Comparison → Affiliate Click → Retailer Purchase → Commission`.

ARIKARTECH is **not** an ecommerce store:
- No cart or checkout
- No payment processing
- No warehouse, shipping, or POS
- No marketplace seller accounts

Retailers handle all physical transactions, customer billing, and order fulfillment.

---

## 2. Monorepo Organization
The entire platform is organized as a unified monorepo:

```
/arikartech
├── backend/               # Laravel 11 / PHP 8.4 REST API backend
│   ├── app/
│   │   ├── Console/Commands/ (CPU-Safe batch cron commands)
│   │   ├── Http/Controllers/Api/V1/ (Public & Admin API controllers)
│   │   ├── Http/Resources/Api/V1/ (Consistent JSON data formatting)
│   │   ├── Models/ (Production Eloquent models)
│   │   └── Services/
│   │       ├── Affiliate/ (AffiliateProviderInterface & Amazon PA-API connector)
│   │       ├── Audit/ (Audit logging for sensitive administrative actions)
│   │       ├── Automation/ (CpuSafeIngestionOrchestrator with atomic locks)
│   │       ├── Health/ (Shared-hosting lightweight system diagnostics)
│   │       ├── Matching/ (Identifier-first O(1) canonical product matcher)
│   │       ├── Pricing/ (BestPriceService & PriceFreshnessService)
│   │       └── SEO/ (StructuredDataService, MetadataService, SitemapService)
│   ├── config/ (Application, automation, sanctum, permissions config)
│   ├── database/migrations/ (Complete production schema)
│   └── tests/ (Feature & Unit PHPUnit test suite)
├── admin/                 # Custom React 18+ / Vite / TypeScript / Tailwind SPA
│   ├── src/
│   │   ├── components/ (Layout, Sidebar, Header, UI primitives)
│   │   ├── contexts/ (AuthContext, MarketContext)
│   │   ├── pages/ (17 dedicated administration views)
│   │   └── services/ (Axios API client with auth interceptors)
├── public/                # Next.js 15 App Router / React / TypeScript / Tailwind Frontend
│   ├── app/
│   │   ├── [market]/ (Localized routing: /us, /uk, /de, /fr, etc.)
│   │   │   ├── products/[slug]/ (Canonical product detail & offer comparison)
│   │   │   ├── categories/[slug]/ (Category discovery)
│   │   │   ├── compare/ (Side-by-side hardware comparison)
│   │   │   └── search/ (Crawl-safe search interface)
│   │   ├── api/out/[offerId]/ (Outbound affiliate tracking & redirection)
│   │   ├── robots.txt/ & sitemap.xml/
│   ├── components/ (ProductCard, OfferComparisonTable, StructuredData, GA4)
│   └── lib/ (API client with ISR revalidation)
├── shared/                # Shared TypeScript contracts, models, and enums
└── docs/                  # In-depth architectural & operational documentation
```

---

## 3. Technology Stack Decisions
- **Backend**: Laravel 11 on PHP 8.4+ with Sanctum, Spatie Permission, and Laravel HTTP Client.
- **Admin**: Custom React Single Page Application built with Vite. No heavy admin frameworks (no Filament, Livewire, or Nova).
- **Public Frontend**: Next.js 15 App Router with SSR/ISR, crawlable HTML, Schema.org JSON-LD, and Google Analytics 4 integration.
- **Database**: MySQL for production; ANSI-standard SQL syntax and indexing for SQLite testing compatibility.
- **Shared-Hosting Compatibility**: Zero requirement for Redis, Supervisor, permanent Node daemons, or Elasticsearch. Runs reliably on standard PHP-FPM / Apache / Nginx + Cron.
