# ARIKARTECH — Search Intelligence & Keyword Analytics

## 1. Search Query Logging Architecture
Every search query submitted on public product catalog routes (`/{market}/search?q=...`) is recorded in the `search_logs` table:
- `query`: Cleaned search term (up to 255 chars).
- `market_id`: Country market where search originated.
- `results_count`: Number of matching canonical products returned.
- `ip_hash`: Privacy-safe SHA-256 hashed IP address (`hash('sha256', $ip . config('app.key'))`).
- `created_at`: Timestamp indexed for historical reporting.

---

## 2. SEO Content Intelligence Insights
In the Admin Dashboard (`/seo/search-overview`), editors and analysts inspect:
1. **Top Queries**: Identifies the most frequently searched tech products.
2. **Zero-Result Gaps (`results_count = 0`)**: Uncovers commercial-intent search queries where no canonical product currently exists, informing targeted affiliate feed imports.
3. **Market Breakdowns**: Compares search volumes across US, UK, EU, and Australasia.
