# ARIKARTECH Production Deployment Guide

## 1. Prerequisites
- PHP 8.4+ with `pdo_mysql`, `mbstring`, `curl`, `xml`, `bcmath`, `gd`, `zip`
- Composer 2.7+
- Node.js 20+ & npm
- MySQL 8.0+
- Web server (Apache with `mod_rewrite` or Nginx)

---

## 2. Step-by-Step Production Deployment

### 1. Clone & Configure Monorepo
```bash
git clone <repository_url> arikartech
cd arikartech
cp .env.example backend/.env
```

### 2. Configure Backend Environment
Edit `backend/.env` with your real MySQL database credentials and application URLs:
```env
APP_NAME=ARIKARTECH
APP_ENV=production
APP_DEBUG=false
APP_URL=https://arikartech.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=arikartech_prod
DB_USERNAME=arikartech_user
DB_PASSWORD=YOUR_STRONG_PASSWORD

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### 3. Install Dependencies & Generate Keys
```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

### 4. Run Migrations & System Initialization
```bash
php artisan migrate --force
php artisan system:init-foundation --admin-email=admin@arikartech.com --admin-password="YOUR_SECURE_PASSWORD"
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Build Admin SPA & Public Frontend
```bash
# In monorepo root:
npm install
npm run build:all
```

### 6. Setup Cron Job
Add the following entry to your hosting crontab (`crontab -e`):
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```
