# ARIKARTECH

> **Global Technology Shopping Discovery, Price-Comparison & Affiliate Platform**

ARIKARTECH is a production-grade technology product discovery and price comparison platform built for organic SEO discovery and multi-retailer monetization.

---

## Business Model
`Organic SEO Discovery → Product Comparison → Retailer Offer Selection → Affiliate Referral → Commission`

ARIKARTECH is **not** an ecommerce store (no checkout, cart, payments, inventory, or fulfillment). All sales and order fulfillment are handled directly by authorized retailers.

---

## Monorepo Architecture

```
/arikartech
├── backend/               # Laravel 11 / PHP 8.4 REST API (/api/v1/...)
├── admin/                 # Custom React 18+ / Vite / TypeScript / Tailwind CSS SPA
├── public/                # Next.js 15 App Router SEO Public Frontend
├── shared/                # Shared TypeScript contracts and types
└── docs/                  # In-depth architectural & deployment documentation
```

---

## Core Technical Features
- **One Canonical Product Model**: Many store offers mapped to a single canonical entity with indexed identifier matching (`UPC`, `EAN`, `GTIN`, `ASIN`, `MPN`).
- **Materialized Best Prices**: High-speed pre-aggregated pricing index for instant catalog filtering and sorting.
- **CPU-Safe Automation**: Fully bounded, resumable, rate-limited batch processing with mutual exclusion locks for shared-hosting stability.
- **Shared-Hosting First**: Operates seamlessly on standard PHP 8.4 + MySQL + Apache/Nginx + Cron environments with zero Redis/Supervisor dependencies.
- **Real Data Only**: No fake products, mock prices, or fabricated reviews. Honest empty states throughout.
- **SEO & Google From Day One**: SSR, Schema.org JSON-LD (`Product`, `AggregateOffer`, `Offer`, `BreadcrumbList`, `WebSite`), dynamic partitioned XML sitemaps, and Google Analytics 4 event architecture.

---

## Quickstart

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan system:init-foundation
php artisan test
```

### Frontend & Admin Setup
```bash
# In monorepo root:
npm install
npm run build:all
```

---

## Documentation
Refer to the `docs/` directory for detailed documentation:
- [Final Launch Audit](docs/FINAL_LAUNCH_AUDIT.md)
- [Final Production Deployment](docs/FINAL_PRODUCTION_DEPLOYMENT.md)
- [Final Launch Checklist](docs/FINAL_LAUNCH_CHECKLIST.md)
- [Final Launch Report](docs/FINAL_LAUNCH_REPORT.md)
- [System Architecture](docs/ARCHITECTURE.md)
- [Database Schema & ERD](docs/DATABASE.md)
- [REST API Specifications](docs/API.md)
- [Authentication & RBAC](docs/AUTHENTICATION.md)
- [Affiliate Provider Engine](docs/AFFILIATE_ENGINE.md)
- [SEO & Structured Data](docs/SEO.md)
- [CPU-Safe Automation](docs/AUTOMATION.md)
- [Shared Hosting Guide](docs/SHARED_HOSTING.md)
- [Security Architecture](docs/SECURITY.md)
- [Testing Guide](docs/TESTING.md)
- [Phase Status Report](docs/PHASE_STATUS.md)
