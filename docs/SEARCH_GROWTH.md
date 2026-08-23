# ARIKARTECH — Search Growth Engine & Zero-Result Intelligence

## 1. Multi-Index Search Ranking Architecture

Search queries are evaluated in indexed hierarchical order:
1. **Exact Canonical Identifier Match**: Instant lookup on `GTIN`, `EAN`, `UPC`, `ASIN`, or `MPN`.
2. **Exact Model Number Match**: Direct matching on `model_number`.
3. **Brand + Category Match**: Fast indexed filtering on `brand_id` and `category_id`.
4. **Product Name Prefix & Fuzzy Matching**: Substring matching with SQL indexes.

---

## 2. Zero-Result Intelligence & Opportunity Scoring
Every search query on public routes is recorded in `search_logs`.
The Admin Search Intelligence dashboard computes an **Opportunity Score**:
$$\text{Opportunity Score} = (\text{Search Count} \times 15) + \text{Recency Boost}$$

### Growth Workflow:
1. Identify high-opportunity queries with `results_count = 0` (e.g. `RTX 5080 Super`).
2. Run targeted affiliate feed ingestion for the missing hardware model.
3. Automatically satisfy search intent on next crawl.
