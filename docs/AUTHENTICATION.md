# ARIKARTECH Authentication & RBAC Architecture

## 1. Authentication Strategy

### Administrative Staff
- Secure email/password authentication issuing Laravel Sanctum plain-text Bearer tokens.
- Tokens are stored locally on the client and injected into every API request via the Authorization header:
  `Authorization: Bearer <token>`
- Token revocation on logout deletes the specific personal access token database record.

### Customers / Public Visitors
- **Zero Authentication Wall for Retailer Outbound Clicks**: Visitors are never forced to create an account or log in to view deals or click retailer affiliate links.
- Customer accounts are optional, intended strictly for future saved comparisons, price drop notifications, and preferences.
- Google OAuth is integrated via Laravel Socialite with state verification.

---

## 2. Role-Based Access Control (RBAC)
Role-based permissions are enforced server-side using Spatie Laravel Permission middleware. Frontend UI elements adaptively reflect permissions, but the backend is the authoritative security boundary.

### Roles & Permissions Matrix

| Permission | Super Admin | Admin | Editor | Analyst |
| :--- | :---: | :---: | :---: | :---: |
| `catalog.view` | ✅ | ✅ | ✅ | ✅ |
| `catalog.create` | ✅ | ✅ | ✅ | ❌ |
| `catalog.edit` | ✅ | ✅ | ✅ | ❌ |
| `catalog.delete` | ✅ | ✅ | ❌ | ❌ |
| `offers.view` | ✅ | ✅ | ✅ | ✅ |
| `offers.manage` | ✅ | ✅ | ❌ | ❌ |
| `affiliates.view` | ✅ | ✅ | ❌ | ❌ |
| `affiliates.manage` | ✅ | ✅ | ❌ | ❌ |
| `analytics.view` | ✅ | ✅ | ❌ | ✅ |
| `seo.view` | ✅ | ✅ | ✅ | ❌ |
| `seo.manage` | ✅ | ✅ | ✅ | ❌ |
| `automation.view`| ✅ | ✅ | ❌ | ✅ |
| `automation.trigger`| ✅ | ✅ | ❌ | ❌ |
| `settings.view` | ✅ | ✅ | ❌ | ❌ |
| `settings.manage`| ✅ | ❌ | ❌ | ❌ |
| `users.manage` | ✅ | ❌ | ❌ | ❌ |

---

## 3. Initial Administrative Credentials
The foundation setup command (`php artisan system:init-foundation`) creates the default Super Admin user:
- **Email**: `admin@arikartech.com`
- **Initial Password**: `ChangeMeProduction123!`
- **Role**: `Super Admin`

> [!CAUTION]
> Change the default password upon initial production deployment using the admin interface or Artisan CLI.
