# ARIKARTECH — Production Administrator Setup & Security Guide

## 1. Idempotent Admin Seeding Command

To initialize or verify the primary Super Administrator account:

```bash
# Optional: customize credentials via environment variables
export ARIKARTECH_ADMIN_NAME="Super Administrator"
export ARIKARTECH_ADMIN_EMAIL="admin@arikartech.com"
export ARIKARTECH_ADMIN_PASSWORD="YourSecurePasswordHere"

# Execute idempotent seeder
php artisan admin:seed

# Force password update on existing account
php artisan admin:seed --force
```

---

## 2. Admin Verification Command
To safely check administrator account status without logging or exposing password hashes:

```bash
php artisan admin:status
```

### Sample Output:
```text
+----------------------+---------------------------+------------------------------------+
| Parameter            | Status / Value            | Details                            |
+----------------------+---------------------------+------------------------------------+
| Administrator Exists | YES                       | Account registered in database     |
| Admin Name           | Admin User                | Display name                       |
| Admin Email          | admin@arikartech.com      | Primary authentication identifier  |
| Assigned Roles       | Super Admin               | Role-based access control tier     |
| Active Permissions   | 28                        | Total granular permissions granted |
| Account Status       | ACTIVE                    | Account operational state          |
+----------------------+---------------------------+------------------------------------+
```
