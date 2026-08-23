# ARIKARTECH — Production Deployment Guide

## 1. Target Infrastructure Stack
- **OS**: Linux (Ubuntu 22.04 LTS or Standard Shared cPanel/Plesk)
- **Web Server**: Nginx or Apache with `mod_rewrite`
- **PHP**: 8.4+ (Extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `xml`, `bcmath`)
- **Database**: MySQL 8.0+ / MariaDB 10.5+
- **Node.js**: 20+ (for building frontend assets)
- **Zero Permanent Daemons**: No Redis, Elasticsearch, Supervisor, or long-running Node processes required.

---

## 2. Step-by-Step Deployment Procedure

### Step 1: Clone and Configure Environment
```bash
git clone https://github.com/arikartech/arikartech.git /var/www/arikartech
cd /var/www/arikartech

# Configure backend environment
cp backend/.env.example backend/.env
# Edit backend/.env with your production database credentials, APP_KEY, and APP_URL
```

### Step 2: Install Backend Dependencies & Run Migrations
```bash
cd /var/www/arikartech/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan system:init-foundation
```

### Step 3: Verify Production Readiness
```bash
php artisan system:production-readiness
```

### Step 4: Build Frontend Assets
```bash
cd /var/www/arikartech
npm install
npm run build:all
```

### Step 5: Configure Production Web Server & SSL
Point your public web root to the appropriate build directories:
- **API**: `https://api.arikartech.com` -> `/var/www/arikartech/backend/public`
- **Admin**: `https://admin.arikartech.com` -> `/var/www/arikartech/admin/dist`
- **Public**: `https://arikartech.com` -> Next.js deployment / standalone static export

### Step 6: Configure Single Cron Entry
Add the following single cron job to the server crontab:
```cron
* * * * * cd /var/www/arikartech/backend && php artisan schedule:run >> /dev/null 2>&1
```
