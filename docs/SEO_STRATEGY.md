# ARIKARTECH — Production SEO & Structured Data Strategy

## 1. Technical SEO Architecture
ARIKARTECH generates 100% of its organic revenue through high-intent search discovery. The technical SEO architecture enforces strict quality thresholds:

1. **Indexation Eligibility Guard (`SeoEligibilityService`)**:
   - Status MUST be `published`.
   - MUST possess canonical name and slug.
   - MUST possess at least one active, fresh retailer offer in the requested market.
   - Products failing this criteria return `robots: noindex, follow` to protect domain authority.

2. **Schema.org Structured Data (`StructuredDataService`)**:
   - `Product` entity with `brand`, `model`, `sku`, `gtin13` / `gtin12`.
   - `AggregateOffer` or `Offer` with `priceCurrency`, `price`, `availability: InStock / OutOfStock`, `seller` (`Organization`), `url`.
   - `BreadcrumbList` navigation schema.

3. **Multi-Region `hreflang` Handling**:
   - Injects canonical alternate URLs for all active markets (e.g. `en-us`, `en-gb`, `de`, `fr`, `es`, `it`, `nl`, `en-au`, `en-nz`).

4. **Dynamic Sitemaps**:
   - `/sitemap.xml`: Multi-market sitemap index linking to active product, category, and comparison routes.
