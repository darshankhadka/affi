# ARIKARTECH — PRODUCTION RUNBOOK & OPERATIONS GUIDE

## 1. System Architecture & Requirements
- **Runtime**: PHP 8.4 CLI / FPM, Node.js 20+
- **Frameworks**: Laravel 11 (API & Ingestion Engine), Next.js 15 (Public SEO App), React / Vite (Admin Dashboard)
- **Database**: MySQL 8.0+ (`backend/.env`)
- **No Heavy Background Daemons**: Designed for shared hosting & standalone servers without mandatory Redis, Kafka, or Elasticsearch.

---

## 2. Daily & Scheduled Maintenance Commands

```bash
# 1. Truthful Global Health & Freshness Audit
php artisan affiliate:health-check

# 2. Daily Awin & Network Catalog Ingestion (Bounded)
php artisan automation:sync-market de --limit=25
php artisan automation:sync-market gb --limit=25

# 3. Database Catalog Health & Stale Offer Deactivation
php artisan catalog:health

# 4. Catalog Data Integrity & Domain Contamination Audit
php artisan catalog:awin-integrity

# 5. Programmatic SEO Indexation Audit
php artisan catalog:seo-audit
```

---

## 3. Production Build & Static Export Pipeline

```bash
# Build all workspaces (Shared Types -> Admin Dashboard -> Next.js 15 Public App)
npm run build:all
```

---

## 4. Troubleshooting & Diagnostic Commands

| Symptom | Diagnostic Command | Remediation |
| :--- | :--- | :--- |
| Provider Connection Failure | `php artisan affiliate:test-provider <provider>` | Check `.env` API keys and rate limit headers. |
| Retailer Deep Link Broken | `php artisan affiliate:test-retailer <slug>` | Verify retailer program ID and provider connector. |
| Stale Prices Detected | `php artisan catalog:health` | Run `automation:sync-market` to refresh offers. |
| Unindexable Product Pages | `php artisan catalog:seo-audit` | Verify product has quality score >= 40 and active store offers. |
