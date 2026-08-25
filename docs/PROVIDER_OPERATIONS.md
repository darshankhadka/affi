# ARIKARTECH — Affiliate Provider Operations Runbook

This runbook describes operational management, connectivity testing, credential configuration, and error recovery for Awin, CJ Affiliate, Amazon Associates, and Impact.

---

## 1. Provider Summary Matrix

| Provider | Driver Code | API Type | Credential Keys in `.env` | Admin Controls |
| :--- | :--- | :--- | :--- | :--- |
| **Awin** | `awin` | REST API + GZIP/ZIP Datafeed Stream | `AWIN_API_TOKEN`, `AWIN_PUBLISHER_ID`, `AWIN_DATAFEED_URL`, `AWIN_DATAFEED_API_KEY` | Connect, Test, Sync, Programs, Pause, Errors, Config |
| **CJ Affiliate** | `cj` | GraphQL (`ads.api.cj.com/query`) | `CJ_API_TOKEN`, `CJ_COMPANY_ID`, `CJ_WEBSITE_ID` | Connect, Test, Sync, Programs, Pause, Errors, Config |
| **Amazon Associates** | `amazon` | Mode 1: Manual URL/ASIN Import<br>Mode 2: PA-API 5.0 (AWS SigV4) | `AMAZON_TAG_US`, `AMAZON_TAG_UK`, `AMAZON_TAG_DE`, etc.<br>`AMAZON_PAAPI_KEY`, `AMAZON_PAAPI_SECRET` | Mode 1 Import Modal, Mode 2 Config |
| **Impact** | `impact` | REST API v1 | `IMPACT_ACCOUNT_SID`, `IMPACT_AUTH_TOKEN`, `IMPACT_MEDIA_PARTNER_ID` | Connect, Test, Sync, Pause, Errors, Config |

---

## 2. Live Diagnostic Commands

```bash
# Awin Diagnostic
php artisan affiliate:awin-diagnostic

# CJ Diagnostic
php artisan affiliate:cj-diagnostic

# Amazon Diagnostic
php artisan affiliate:amazon-diagnostic

# Overall System Readiness
php artisan system:production-readiness
```

---

## 3. Redirect Telemetry & Secret Protection

- Outbound affiliate clicks flow through `/go/{offerId}`.
- All tokens, AWS signatures, API keys, and publisher IDs are stripped from error logs and client responses using `App\Support\SecretRedactor`.
