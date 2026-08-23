# ARIKARTECH — Catalog Operations Manual

## 1. Catalog Diagnostic Commands

ARIKARTECH includes 6 dedicated Artisan commands for inspecting catalog health, deduplication, SEO eligibility, and conversion metrics:

| Command | Purpose | Output Format |
| :--- | :--- | :--- |
| `php artisan catalog:health` | Audits database connectivity, provider states, stale offers, and duplicate identifiers. | Tabular Diagnostic |
| `php artisan catalog:stats` | Real-time counts of canonical products, published items, offers, retailers, and clicks. | Numeric Table |
| `php artisan catalog:validate` | Deep integrity check for orphaned offers, invalid prices, and missing affiliate URLs. | Pass/Fail Audit |
| `php artisan catalog:quality` | Deterministic quality scoring distribution across all canonical products. | Score Breakdown (0-100) |
| `php artisan catalog:seo-audit` | Evaluates indexable vs `noindex` products across all 9 target markets. | Market Eligibility Table |
| `php artisan catalog:conversion-stats` | Aggregates search volume, referral CTR, and click distribution across networks. | Funnel Analytics |

---

## 2. Deterministic Product Quality Scoring
The `DataQualityService` computes a deterministic quality score (0 to 100):
- **Title & Slug Completeness**: 10 pts
- **Brand Assigned**: 10 pts
- **Model Number Assigned**: 10 pts
- **Canonical Identifiers (GTIN, EAN, UPC, ASIN, MPN)**: 20 pts
- **Primary / Gallery Images**: 15 pts
- **Technical Specifications**: 15 pts
- **Active Retailer Offers**: 15 pts
- **Description Content**: 5 pts

### Quality Grades:
- `90–100`: **Excellent** (Featured prominently on homepage and high-priority sitemaps)
- `75–89`: **Good** (Eligible for full indexing and comparison tables)
- `60–74`: **Needs Improvement** (Published if active offers exist)
- `<60`: **Not Publishable** (Retained in draft state; returns `noindex, follow`)
