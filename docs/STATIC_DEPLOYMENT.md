# ARIKARTECH — Production Static Deployment Guide (Himalayan Host / Shared Hosting)

This guide documents the production deployment of ARIKARTECH as a decoupled static storefront, Vite admin SPA, and Laravel REST API.

---

## 1. Domain & Document Root Architecture

| Subdomain | Technology | Build Command | Document Root on Server |
| :--- | :--- | :--- | :--- |
| **`arikartech.com`** | Next.js 15 Static HTML Export | `npm run build --workspace=public` | `/home/username/public_html` (contents of `public/out/`) |
| **`admin.arikartech.com`** | React 18 / Vite SPA | `npm run build --workspace=admin` | `/home/username/admin_html` (contents of `admin/dist/`) |
| **`api.arikartech.com`** | Laravel 11 REST API + MySQL | `composer install --no-dev -o` | `/home/username/api_html/public` (Laravel `backend/public/`) |

---

## 2. Server Prerequisites
- **Web Server**: Apache (`mod_rewrite` enabled) or Nginx
- **PHP**: 8.4+ (Extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `xml`, `bcmath`, `fileinfo`)
- **MySQL**: 8.0+ or MariaDB 10.5+
- **Node.js**: Required only on build machine / CI/CD (No Node.js process runs on production shared hosting)

---

## 3. Build & Deployment Steps

### Step 1: Build All Workspaces Locally or in CI
```bash
# In monorepo root:
npm install
npm run build:all
```
This produces:
- `public/out/`: 100% static HTML, CSS, JS, `sitemap.xml`, and `robots.txt`.
- `admin/dist/`: Production Vite React bundle.

### Step 2: Deploy Public Website (`arikartech.com`)
Upload all files inside `public/out/` directly into the document root for `arikartech.com` (e.g. `public_html/`).

Example `.htaccess` for `arikartech.com`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Serve static HTML files directly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME}.html -f
    RewriteRule ^(.*)$ $1.html [L]
    
    # Fallback to 404
    ErrorDocument 404 /404.html
</IfModule>
```

### Step 3: Deploy Admin Dashboard (`admin.arikartech.com`)
Upload all files inside `admin/dist/` directly into the document root for `admin.arikartech.com`.

Example `.htaccess` for `admin.arikartech.com` (SPA fallback):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>
```

### Step 4: Deploy Laravel Backend (`api.arikartech.com`)
1. Upload `backend/` to a secure directory (e.g. `/home/username/backend/`) outside `public_html`.
2. Point the document root for `api.arikartech.com` strictly to `/home/username/backend/public`.
3. Configure `backend/.env` with your MySQL credentials:
   ```dotenv
   APP_NAME=ARIKARTECH
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://arikartech.com
   ADMIN_URL=https://admin.arikartech.com
   API_URL=https://api.arikartech.com
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```
4. Run migrations & foundations:
   ```bash
   php artisan migrate --force
   php artisan system:init-foundation
   php artisan admin:seed
   ```

### Step 5: Configure Single Server Crontab
```cron
* * * * * cd /home/username/backend && php artisan schedule:run >> /dev/null 2>&1
```
