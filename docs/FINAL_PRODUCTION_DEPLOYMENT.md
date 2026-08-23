# ARIKARTECH — Final Production Deployment Runbook

This guide contains the exact, step-by-step instructions to deploy ARIKARTECH on a production Linux server or shared-hosting cPanel/Plesk environment with MySQL 8.0+.

---

## 1. System Requirements
- **PHP**: 8.4+ (Extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `xml`, `bcmath`, `fileinfo`)
- **MySQL**: 8.0+ or MariaDB 10.5+
- **Node.js**: 20+ & npm 10+ (for building frontend assets)
- **Web Server**: Nginx or Apache (`mod_rewrite` enabled)
- **Daemons**: Zero required (No Redis, Elasticsearch, Supervisor, or permanent Node.js processes)

---

## 2. Server Installation & Configuration

### Step 1: Clone Repository
```bash
git clone https://github.com/arikartech/arikartech.git /var/www/arikartech
cd /var/www/arikartech
```

### Step 2: Configure Environment
```bash
cp .env.example backend/.env
# Edit backend/.env with your production database credentials:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=arikartech_prod
# DB_USERNAME=arikartech_user
# DB_PASSWORD=YourSecurePassword
```

### Step 3: Install Backend Dependencies & Generate Key
```bash
cd /var/www/arikartech/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
```

### Step 4: Run Migrations & Initialize Foundations
```bash
# Run database migrations
php artisan migrate --force

# Seed system foundation (Markets, Currencies, Taxonomy, Providers, Super Admin)
php artisan system:init-foundation

# Seed or update Super Admin account
php artisan admin:seed
```

### Step 5: Verify Production Diagnostics
```bash
php artisan system:production-readiness
php artisan catalog:health
php artisan admin:status
```

### Step 6: Build Frontend Assets
```bash
cd /var/www/arikartech
npm install
npm run build:all
```

---

## 3. Web Server & Document Roots

| Application | Subdomain | Document Root |
| :--- | :--- | :--- |
| **Public Storefront** | `https://arikartech.com` | Next.js Node.js server or standalone output |
| **Admin SPA** | `https://admin.arikartech.com` | `/var/www/arikartech/admin/dist` |
| **REST API** | `https://api.arikartech.com` | `/var/www/arikartech/backend/public` |

---

## 4. Single Server Crontab
Add the following single entry to the server crontab (`crontab -e`):

```cron
* * * * * cd /var/www/arikartech/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Live Affiliate Credential Configuration (When Ready)
When affiliate network accounts are approved, add their credentials to `backend/.env`:
```dotenv
# Awin Network
AWIN_API_TOKEN=your_awin_oauth_token
AWIN_PUBLISHER_ID=your_publisher_id

# CJ Affiliate
CJ_API_TOKEN=your_cj_graphql_token
CJ_COMPANY_ID=your_company_id
CJ_WEBSITE_ID=your_website_id

# Impact.com
IMPACT_ACCOUNT_SID=your_account_sid
IMPACT_AUTH_TOKEN=your_auth_token
IMPACT_MEDIA_PARTNER_ID=your_media_partner_id
```
