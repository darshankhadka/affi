# ARIKARTECH Search Engine Optimization (SEO) & Google Architecture

Organic search traffic is the foundation of ARIKARTECH's business model. Every page and URL structure is engineered for search engine crawlability, Core Web Vitals performance, and rich snippet indexation.

---

## 1. URL Architecture & Targeting
- **Country Market Partitioning**: Clean subdirectory routing per country market (`/us/`, `/uk/`, `/de/`, `/fr/`, `/es/`, `/it/`, etc.).
- **Canonical Product Pages**: `/[market]/products/[slug]`
- **Category Discovery Pages**: `/[market]/categories/[slug]`
- **Comparison Pages**: `/[market]/compare?slugs=...`
- **Internal Search**: `/[market]/search?q=...` (served with `robots: noindex, follow` to prevent search result bloat).

---

## 2. Structured Data (Schema.org JSON-LD)
All product pages dynamically output valid Schema.org structured data:
- **`Product` Schema**: Includes `name`, `description`, `image`, `brand`, `model`, and `mpn`.
- **`AggregateOffer` Schema**: Injected when 2 or more offers exist, detailing `lowPrice`, `highPrice`, `priceCurrency`, and `offerCount`.
- **`Offer` Schema**: Injected for each retailer deal with `price`, `availability` (`https://schema.org/InStock`), `itemCondition`, and seller `Organization`.
- **`BreadcrumbList` Schema**: Hierarchical trail for clear Google breadcrumb navigation.
- **`WebSite` Schema**: Injected on the homepage with `SearchAction` deep-link support.

---

## 3. Sitemap & Search Console Infrastructure
- **Master Sitemap Index**: `/sitemap.xml`
- **Partitioned Market Sitemaps**: Dynamic XML sitemaps partitioned by market:
  - `/sitemap-us-products.xml`
  - `/sitemap-us-categories.xml`
  - `/sitemap-uk-products.xml`, etc.
- **Google Search Console Verification**: Meta verification tag configurable via `NEXT_PUBLIC_GSC_VERIFICATION_CODE`.
- **Google Analytics 4**: Event tracking integration (`page_view`, `view_item`, `view_item_list`, `search`, and `affiliate_click`).
