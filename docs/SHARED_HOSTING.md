# ARIKARTECH Shared-Hosting Production Guide

ARIKARTECH is intentionally architected to operate efficiently on standard shared hosting environments (cPanel, Plesk, DirectAdmin, Apache/Nginx, PHP 8.4+, MySQL).

---

## 1. Zero Heavy Daemon Dependencies
The platform operates without requiring:
- ❌ Permanent Node.js daemon (public frontend can run statically or via lightweight Node/SSR where available)
- ❌ Redis server (uses optimized `file` or `database` cache drivers)
- ❌ Supervisor daemon (uses bounded database-backed queue runners and cron triggers)
- ❌ Elasticsearch cluster (uses indexed relational queries and materialized `best_prices` tables)
- ❌ Docker / Kubernetes overhead

---

## 2. Directory Layout & Document Roots on Shared Hosting

In a standard cPanel / Apache setup:

```
/home/username/
├── arikartech/
│   ├── backend/          (Core Laravel code, not accessible via web)
│   ├── admin/dist/       (Static build of React admin)
│   └── public/.next/     (Next.js standalone or export)
└── public_html/          (Web accessible document root)
    ├── index.php         (Points to backend/public/index.php for API)
    ├── admin/            (Symlink or copy of admin/dist/ for admin.arikartech.com)
    └── .htaccess         (Apache mod_rewrite routing)
```

---

## 3. Recommended PHP & MySQL Settings
- **PHP Version**: 8.4+
- **Extensions**: `pdo_mysql`, `mbstring`, `curl`, `xml`, `bcmath`, `gd`, `zip`
- **Memory Limit**: `128M` (Application operates comfortably under 32MB)
- **Max Execution Time**: `300s` (Automation batches cap execution at 240s)

---

## 4. Cache & Session Configuration
In `.env`:
```env
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```
Run `php artisan config:cache` and `php artisan route:cache` upon deployment for optimal bytecode caching and zero filesystem config read overhead.
