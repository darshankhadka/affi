# ARIKARTECH — SEO Architecture & Indexation Playbook

This document details the search engine discovery, structured data, canonicalization, and programmatic SEO system in ARIKARTECH.

---

## 1. Technical SEO Foundation

1. **Deterministic Canonical URLs**: Every page contains a single `<link rel="canonical" href="...">` reflecting the authoritative market route (e.g. `https://arikartech.com/us/products/macbook-pro-14`).
2. **Multi-Market Hreflang Tags**: Pages generate bidirectional `hreflang` tags across all 35 supported regional markets, enabling international discovery without duplicate content penalties.
3. **JSON-LD Schema Markup**:
   - `Product` schema with name, brand, image, identifiers (GTIN, EAN, UPC, MPN).
   - `AggregateOffer` schema with `lowPrice`, `highPrice`, `offerCount`, `priceCurrency`, and stock availability.
   - `BreadcrumbList` schema for structured navigation.
   - `Organization` and `WebSite` schemas on root routes.
4. **Indexation Quality Gate**: Thin pages with no valid product information or expired offers are flagged with `noindex, nofollow` to protect site quality.

---

## 2. Dynamic Sitemap Partitioning

- Sitemaps are partitioned by market and resource type (`/sitemap.xml`, `/sitemap-products.xml`, `/sitemap-categories.xml`, `/sitemap-brands.xml`).
- Sitemaps only list HTTP 200, indexable, canonical URLs.
- Change frequency and `lastmod` timestamps are generated from real database update records.

---

## 3. Static Page Generation (SSG)

Next.js 15 App Router statically pre-renders 1,371 base taxonomy and market paths across 35 countries at build time, with incremental static regeneration (ISR) refreshing product detail pages on demand.
