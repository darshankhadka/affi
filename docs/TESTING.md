# ARIKARTECH Testing Architecture

ARIKARTECH maintains a comprehensive automated testing suite covering unit logic, feature endpoints, RBAC authorization, and data integrity.

---

## 1. Test Suite Breakdown

### Backend Feature Tests (`backend/tests/Feature/`)
- `AuthTest.php`:
  - Verifies email/password authentication and Sanctum Bearer token generation.
  - Verifies rejection of invalid passwords.
  - Verifies token revocation on logout.
- `RbacAuthorizationTest.php`:
  - Verifies rejection of unauthenticated requests (401).
  - Verifies Super Admin access to administrative and user management endpoints.
  - Verifies authorization enforcement preventing lower roles (Analyst) from mutating catalog products (403).
- `ProductCatalogTest.php`:
  - Verifies empty catalog returns honest zero counts.
  - Verifies O(1) indexed identifier matching (UPC, EAN, ASIN, Model).
  - Verifies dynamic SEO metadata and Schema.org structured JSON-LD output.
- `PricingAndFreshnessTest.php`:
  - Verifies best price calculation accurately extracts minimum price and prioritizes in-stock retailer offers.
  - Verifies price history logs snapshots only on genuine price or stock changes.
- `AffiliateClickAndRedirectTest.php`:
  - Verifies outbound click logging with SHA256 hashed IP and 302 redirection.
- `CpuSafeAutomationTest.php`:
  - Verifies batch execution limits, execution record creation in `automation_jobs`, and graceful exit.
- `HealthCheckTest.php`:
  - Verifies `/api/v1/health` diagnostics.

---

## 2. Running Test Suites

### Backend Tests
```bash
cd backend
php artisan test
```

### TypeScript Static Checks & Frontend Builds
```bash
# In Monorepo Root:
npm run build:all
```
