# ARIKARTECH — Production Route Matrix & Audit

## 1. Public Storefront Routes (Next.js 15 App Router)

| Route Path | Page Purpose | Rendering Strategy | Robots Directive |
| :--- | :--- | :--- | :--- |
| `/[market]` | Homepage & Top Deals | Dynamic (SSR) | `index, follow` |
| `/[market]/search?q=...` | Live Catalog Search | Dynamic (SSR) | `noindex, follow` (Crawl Budget Protection) |
| `/[market]/categories/[slug]` | Category Hardware Hub | Dynamic (SSR) | `index, follow` if products exist |
| `/[market]/brands/[slug]` | Brand Hardware Hub | Dynamic (SSR) | `index, follow` if products exist |
| `/[market]/products/[slug]` | Product Detail & Price Compare | Dynamic (SSR) | `index, follow` if $\ge 1$ active offer |
| `/[market]/compare?slugs=...`| Side-by-Side Comparison | Dynamic (SSR) | `index, follow` if $\ge 2$ products |
| `/[market]/about` | Mission & Architecture | Dynamic (SSR) | `index, follow` |
| `/[market]/privacy` | Privacy Policy (Hashed IP) | Dynamic (SSR) | `index, follow` |
| `/[market]/terms` | Terms of Service | Dynamic (SSR) | `index, follow` |
| `/[market]/disclosure` | FTC / ASA Affiliate Disclosure | Dynamic (SSR) | `index, follow` |
| `/[market]/contact` | Support & Merchant Inquiries | Dynamic (SSR) | `index, follow` |
| `/api/out/[offerId]` | Affiliate Outbound Redirect | Dynamic API Route | `noindex, nofollow` (302 Redirect) |
| `/sitemap.xml` | Partitioned XML Sitemap Index | Static / Cached | `index, follow` |
| `/robots.txt` | Crawler Directives | Static | — |

---

## 2. Admin & Editorial Routes (React / Vite SPA)

| Route Path | Module Name | Permission Tier |
| :--- | :--- | :--- |
| `/` | Live Dashboard Overview | `Super Admin`, `Admin`, `Editor`, `Analyst` |
| `/catalog/products` | Canonical Product Index | `Super Admin`, `Admin`, `Editor`, `Analyst` |
| `/catalog/products/:id` | Product Inspector & Edit | `Super Admin`, `Admin`, `Editor` |
| `/catalog/categories` | Category Hierarchy CRUD | `Super Admin`, `Admin`, `Editor` |
| `/catalog/brands` | Brand Directory CRUD | `Super Admin`, `Admin`, `Editor` |
| `/catalog/matching` | Identifier Matching Engine | `Super Admin`, `Admin`, `Editor` |
| `/prices/offers` | Store Offer Index | `Super Admin`, `Admin`, `Editor`, `Analyst` |
| `/prices/best` | Materialized Best Prices | `Super Admin`, `Admin`, `Editor`, `Analyst` |
| `/prices/history` | Historical Price Log | `Super Admin`, `Admin`, `Editor`, `Analyst` |
| `/affiliates/providers` | Affiliate Network Hub | `Super Admin`, `Admin` |
| `/affiliates/retailers` | Merchant Retailer CRUD | `Super Admin`, `Admin` |
| `/affiliates/performance`| Conversion & Click Analytics | `Super Admin`, `Admin`, `Analyst` |
| `/seo/overview` | Search & Sitemap Hub | `Super Admin`, `Admin`, `Editor` |
| `/analytics/search` | Search Intelligence & Gaps | `Super Admin`, `Admin`, `Analyst` |
| `/automation/status` | Ingestion Monitor | `Super Admin`, `Admin` |
| `/system/users` | User & RBAC Management | `Super Admin` |
| `/system/settings` | Platform Settings | `Super Admin`, `Admin` |
