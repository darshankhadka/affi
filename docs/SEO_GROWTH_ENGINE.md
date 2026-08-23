# ARIKARTECH — SEO Growth Engine & Programmatic Architecture

## 1. Scalable Programmatic Route Matrix

| Route Structure | Page Type | Content Scope | Indexation Guard |
| :--- | :--- | :--- | :--- |
| `/{market}/` | Homepage | Top deals & category grid | `index, follow` |
| `/{market}/categories/{slug}` | Category Page | Real category hardware list | `index, follow` if products exist |
| `/{market}/brands/{slug}` | Brand Page | Brand-filtered catalog | `index, follow` if products exist |
| `/{market}/products/{slug}` | Product Detail | Full specs & retailer price comparison | `index, follow` if $\ge 1$ active offer |
| `/{market}/compare?slugs=...` | Comparison | Multi-model side-by-side specs | `index, follow` if $\ge 2$ products |
| `/{market}/search?q=...` | Search Results | Dynamic query matching | `noindex, follow` (Crawl Budget Guard) |

---

## 2. Multi-Market Sitemaps & Dynamic Indexation
- The root `/sitemap.xml` dynamically partitions sitemaps across active markets.
- Inactive products, products without active offers, and zero-result pages are automatically excluded from XML sitemaps to protect domain crawl budget.
