# ARIKARTECH — RETAILER ONBOARDING PLAYBOOK

## Overview
This runbook explains how to onboard a new merchant or activate an existing merchant from the locked 35-market matrix.

---

## 1. Retailer Lifecycle & Integration States

| Status | Meaning | Action Required |
| :--- | :--- | :--- |
| `not_configured` | Retailer defined in matrix, but no affiliate credentials or joined programme exists. | Apply to merchant program or configure API key. |
| `application_required` | Network requires publisher application approval before deep links or feeds are enabled. | Submit application in affiliate portal. |
| `pending_approval` | Application submitted to merchant; awaiting merchant affiliate manager sign-off. | Monitor network notifications. |
| `approved` | Merchant approved publisher; credentials ready for live activation. | Enter credentials in Admin UI or backend `.env`. |
| `connected` | Live connection tested and verified with real merchant catalog offers. | Normal automated synchronization enabled. |
| `temporarily_disabled` | Merchant paused or maintenance mode. | Automated sync skipped until re-enabled. |
| `error` | Sync failure or credentials revoked. | Check `last_error` and execute `affiliate:test-retailer`. |

---

## 2. Onboarding Workflow

### Step 1: Verify Matrix Entry
Check if the retailer exists in the database:
```bash
php artisan affiliate:test-retailer <retailer-slug>
```

### Step 2: Configure Provider Credentials
If the retailer belongs to an affiliate network (e.g. Awin programme, CJ website, Impact campaign):
1. Navigate to **Admin → Affiliate → Retailers**.
2. Select the retailer and click **Configure**.
3. Link the appropriate `affiliate_provider_id` and enter the merchant's network `program_identifier` (e.g. Awin advertiser ID).

### Step 3: Test Connection & Deeplinks
Execute the CLI diagnostic command:
```bash
php artisan affiliate:test-retailer <retailer-slug>
```
Verify that the sample deeplink resolves correctly with market attribution sub-IDs.

### Step 4: Run Dry-Run Ingestion
Perform a safe, bounded test ingestion:
```bash
php artisan affiliate:sync <retailer-slug> --limit=5 --dry-run
```

### Step 5: Live Activation
Trigger a bounded live sync:
```bash
php artisan affiliate:sync <retailer-slug> --limit=20
```
Verify imported offers via:
```bash
php artisan catalog:awin-integrity
php artisan catalog:health
```
