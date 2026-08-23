# ARIKARTECH REST API Documentation

All API endpoints are versioned under the `/api/v1/` namespace and return uniform JSON payloads.

---

## 1. Response Structure

### Success Response
```json
{
  "success": true,
  "message": "Optional status message",
  "data": { ... },
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 95
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Detailed error message",
  "errors": {
    "field_name": ["Validation error description"]
  }
}
```

---

## 2. Public Endpoints

### System Health
- `GET /api/v1/health`
  - Returns database connection status, cache access, stale offers count, and affiliate connector status.

### Authentication
- `POST /api/v1/auth/login` (email, password) → `{ token, user }`
- `GET /api/v1/auth/google` → `{ url }`
- `GET /api/v1/auth/google/callback` → `{ token, user }`

### Markets & Categories
- `GET /api/v1/markets` (List all active regional markets)
- `GET /api/v1/markets/{code}`
- `GET /api/v1/categories` (Category hierarchy tree with product counts)
- `GET /api/v1/categories/{slug}`
- `GET /api/v1/brands`
- `GET /api/v1/brands/{slug}`

### Products & Offers
- `GET /api/v1/products?market=us&category=laptops&brand=apple&q=m3&sort=price_asc&page=1`
- `GET /api/v1/products/{slug}?market=us`
  - Returns product specifications, variants, best_price summary, active retailer offers, and Schema.org JSON-LD in `meta.structured_data`.
- `GET /api/v1/compare?slugs=macbook-pro-14-m3,dell-xps-14&market=us`
- `GET /api/v1/products/{productId}/offers?market_id=1`
- `GET /api/v1/products/{productId}/price-history?days=90`

### Outbound Affiliate Referral
- `GET /api/v1/affiliates/out/{offerId}`
  - Logs click to `affiliate_clicks` with SHA256 hashed IP and returns a `302 Found` redirect to the affiliate destination.

---

## 3. Admin Endpoints (`auth:sanctum` + RBAC)

### Dashboard
- `GET /api/v1/admin/dashboard` (Live real-time operational telemetry)

### Catalog & Product Matching
- `GET /api/v1/admin/products`
- `POST /api/v1/admin/products`
- `GET /api/v1/admin/products/{id}`
- `PUT /api/v1/admin/products/{id}`
- `DELETE /api/v1/admin/products/{id}`
- `POST /api/v1/admin/products/match` (Test matching pipeline)

### Offers & Prices
- `GET /api/v1/admin/offers`
- `POST /api/v1/admin/offers`
- `PUT /api/v1/admin/offers/{id}`
- `DELETE /api/v1/admin/offers/{id}`
- `POST /api/v1/admin/offers/recalculate/{productId}`

### Affiliate Providers & Retailers
- `GET /api/v1/admin/affiliates/providers`
- `PUT /api/v1/admin/affiliates/providers/{id}`
- `GET /api/v1/admin/affiliates/retailers`
- `POST /api/v1/admin/affiliates/retailers`

### Automation & SEO
- `GET /api/v1/admin/automation/jobs`
- `POST /api/v1/admin/automation/batch` (Trigger bounded CPU-safe batch)
- `GET /api/v1/admin/seo/overview`
- `GET /api/v1/admin/seo/redirects`
- `POST /api/v1/admin/seo/redirects`
- `DELETE /api/v1/admin/seo/redirects/{id}`
- `GET /api/v1/admin/users`
- `POST /api/v1/admin/users`
- `PUT /api/v1/admin/users/{id}`
