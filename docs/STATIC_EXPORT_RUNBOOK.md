# ARIKARTECH — Static Export Operational Runbook

## 1. Catalog Regeneration & Build Lifecycle

When new affiliate product feeds are ingested into Laravel and MySQL, the static public storefront can be rebuilt and redeployed on a controlled schedule.

```
+---------------------------+
| Affiliate Networks        | (CJ, Awin, Impact)
+---------------------------+
              |
              v (Cron / Artisan Commands)
+---------------------------+
| Laravel Ingestion Engine  | (Rate-limited, bounded RAM <= 32MB)
+---------------------------+
              |
              v
+---------------------------+
| MySQL Database            | (Canonical Products, Best Prices)
+---------------------------+
              |
              v (Triggered Rebuild / GitHub Actions / Local Build)
+---------------------------+
| Next.js Static Export     | (npm run build in public/)
+---------------------------+
              |
              v
+---------------------------+
| Static HTML (public/out)  | (Zero Node.js dependency)
+---------------------------+
```

---

## 2. Rebuild Command Sequence
To rebuild the static catalog after new products have been ingested:

```bash
# Set production API URL
export NEXT_PUBLIC_API_URL="https://api.arikartech.com/api/v1"
export NEXT_PUBLIC_SITE_URL="https://arikartech.com"

# Build static storefront
cd public
npm run build

# Deploy output
# Copy contents of public/out/ to web server document root
```

---

## 3. Real-Time vs Static Data Boundary

- **Build-Time Static Assets**:
  - Pre-rendered product specification pages (`/us/products/...`)
  - Category and brand hardware index hubs
  - Multi-market SEO metadata, JSON-LD structured data
  - Partitioned `sitemap.xml` and `robots.txt`
- **Runtime Browser-to-API Calls**:
  - Live query catalog search (`/us/search?q=...`)
  - Dynamic spec comparison query (`/us/compare?slugs=...`)
  - Outbound affiliate click tracking and 302 redirection (`https://api.arikartech.com/api/v1/affiliates/out/{offerId}`)
  - Search gap telemetry and conversion analytics logging
