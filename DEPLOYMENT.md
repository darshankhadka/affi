# ARIKARTECH — Production Deployment Guide

This guide outlines the exact, safe production deployment and maintenance workflow for ARIKARTECH on standard Linux and shared-hosting environments without requiring permanent daemons, Redis, or Elasticsearch.

---

## 1. Production Architecture Overview

- **Backend**: Laravel 11 REST API (`/backend`)
- **Admin**: React + Vite + TypeScript SPA (`/admin`)
- **Public Website**: Next.js 15 App Router (`/public`)
- **Database**: MySQL 8.0+

---

## 2. Server Requirements

- **PHP**: 8.2 or 8.4 with extensions: `pdo_mysql`, `curl`, `json`, `mbstring`, `openssl`, `zlib` (for GZIP streaming)
- **Node.js**: 20.x or 22.x LTS + npm
- **Web Server**: Nginx or Apache with URL rewriting enabled (`mod_rewrite`)
- **Cron**: Standard crontab for scheduled batch runs

---

## 3. Initial Deployment Steps

```bash
# 1. Clone or pull repository
cd /path/to/webroot/affi
git pull origin main

# 2. Configure Backend Environment
cd backend
cp .env.example .env
# Edit .env with production database credentials, APP_KEY, and affiliate credentials
php artisan key:generate

# 3. Install backend dependencies & initialize database
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan system:init-foundation
php artisan affiliate:seed-approved-programmes
php artisan optimize

# 4. Build Frontend Workspaces
cd ..
npm ci
npm run build:all

# 5. Verify Production Readiness
cd backend
php artisan system:production-readiness
php artisan affiliate:awin-diagnostic
php artisan affiliate:cj-diagnostic
php artisan affiliate:amazon-diagnostic
```

---

## 4. Recurring Automation & Crontab Setup

Add the standard Laravel scheduler entry to your crontab:

```bash
* * * * * cd /path/to/webroot/affi/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks Configured in `backend/routes/console.php`:
- **CJ Ingestion**: Every 15 minutes (`automation:ingest-provider --provider=cj --market=us --limit=25`)
- **Awin Ingestion**: Every 30 minutes (`automation:ingest-provider --provider=awin --market=uk --limit=25`)
- **Impact Ingestion**: Hourly (`automation:ingest-provider --provider=impact --market=us --limit=25`)
- **Price Refresh & Staleness Check**: Hourly (`automation:refresh-prices --batch=25`)
- **Best Price Recalculation**: Hourly (`pricing:recalculate`)
- **Data Quality Audit**: Daily at 03:00 UTC (`DataQualityService::audit()`)

All automation commands are protected by atomic mutex locks and CPU-safe memory/runtime bounds.

---

## 5. Ongoing Update / Rolling Release Procedure

```bash
cd /path/to/webroot/affi

# 1. Pull latest code
git pull origin main

# 2. Update backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

# 3. Update frontends
cd ..
npm ci
npm run build:all
```

---

## 6. Server File Permission Guidelines

Ensure the web server user (`www-data` or your cPanel user) has write permissions on:
- `backend/storage/`
- `backend/bootstrap/cache/`
- `public/.next/` (if using standalone node server) or static `public/out/`
