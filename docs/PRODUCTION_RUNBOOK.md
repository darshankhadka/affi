# ARIKARTECH — Production Runbook & Operations Guide

## 1. Routine Operational Commands

| Task | Command | Recommended Schedule |
| :--- | :--- | :--- |
| **System Readiness Audit** | `php artisan system:production-readiness` | Before/after deployments |
| **Catalog Health Audit** | `php artisan catalog:health` | Daily (03:00) |
| **Integrity Validation** | `php artisan catalog:validate` | Weekly |
| **Admin Status Check** | `php artisan admin:status` | On demand |
| **Single Cron Schedule** | `php artisan schedule:run` | Every minute via system crontab |

---

## 2. Emergency Incident Response

### Case 1: External Provider Outage (HTTP 500 / 429)
1. **Behavior**: Ingestion pauses automatically with exponential backoff.
2. **Action**: Check `/admin/affiliates/providers` to view error response. Existing catalog data remains protected and intact.

### Case 2: Stale Price Refresh
1. Run manually:
   ```bash
   php artisan automation:refresh-prices --batch=50
   php artisan pricing:recalculate
   ```
