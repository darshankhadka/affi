# ARIKARTECH — Canonical Ingestion & Duplicate Prevention Pipeline

## 1. The Ingestion Pipeline

```
Provider API / Product Feed
            ↓
RawProductDTO
            ↓
ProductNormalizer (Strips promotional noise, normalizes brand & model numbers)
            ↓
NormalizedProductDTO
            ↓
ProductMatchingService (O(1) Indexed Identifier Matching)
   ├── Match Found (GTIN/EAN/UPC/ASIN/MPN) ──> Attach Offer to Existing Canonical Product
   └── No Match ──> Create Canonical Product + Attach Offer
            ↓
BestPriceService (Calculate in-stock min/max price & record PriceHistory snapshot on shift)
            ↓
SeoEligibilityService (Verify active offer requirement before indexation)
```

---

## 2. Identifier Matching Hierarchy

Authoritative identifier order:
1. **GTIN** (Global Trade Item Number, 14 digits)
2. **EAN** (European Article Number, 13 digits)
3. **UPC** (Universal Product Code, 12 digits)
4. **ASIN** (Amazon Standard Identification Number, 10 chars)
5. **MPN** (Manufacturer Part Number, normalized alphanumeric)
6. **Brand + Model Exact Match**

> [!IMPORTANT]
> Fuzzy or probabilistic title similarity is NEVER used to automatically merge products. If identifier matching is ambiguous, a new canonical product is created with status `MATCH_REVIEW_REQUIRED` to guarantee zero false-positive merges.

---

## 3. Best Price Materialization
The `best_prices` table is materialized in the database for sub-millisecond query performance:
- `min_price`: Lowest price among verified active offers in that market.
- `max_price`: Highest active offer price in that market.
- `best_offer_id`: Points to the lowest **in-stock** offer.
- `in_stock_offer_count`: Total active offers with status `in_stock`.
- `offer_count`: Total active offers in that market.
