# ARIKARTECH Security Architecture

## 1. Core Security Controls

### API Authentication & Authorization
- Every sensitive API endpoint requires Bearer token authentication via Laravel Sanctum (`auth:sanctum`).
- Granular role-based authorization is enforced at the controller layer via Spatie Permission middleware (`role:Super Admin|Admin|...`).
- Tokens are hashed using SHA256 before storage in `personal_access_tokens`.

### Protection Against Common Vulnerabilities
- **SQL Injection**: Handled exclusively via Eloquent ORM and parameterized PDO statements with zero raw user-input string interpolation.
- **Cross-Site Scripting (XSS)**: Clean React and Next.js JSX string escaping by default; all raw markdown/HTML rendered on server is strictly sanitized.
- **Cross-Site Request Forgery (CSRF)**: API routes use stateless token authentication.
- **Mass Assignment**: All models strictly declare `$fillable` attributes.
- **Rate Limiting**: Public endpoints enforce rate limits via Laravel `throttle` middleware (`throttle:api`, `throttle:60,1` on outbound clicks).

---

## 2. Secrets & Credential Management
- **Never Commit Secrets**: All API keys, database passwords, OAuth client secrets, and affiliate tags reside exclusively in `.env`.
- **Encrypted Provider Config**: API credentials stored in `affiliate_providers.config` are encrypted at rest using Laravel's `encrypted:array` model cast.
- **Visitor Privacy**: Outbound click logs record a one-way `hash('sha256', $ip . config('app.key'))` rather than raw IP addresses, complying with GDPR and privacy frameworks while maintaining fraud prevention integrity.

---

## 3. Audit Logging
Every administrative mutation (product creation, offer pricing override, provider configuration, role changes) is recorded in the `audit_logs` table with the user ID, timestamp, before/after values, and IP address.
