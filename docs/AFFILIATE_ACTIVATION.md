# ARIKARTECH — REAL AFFILIATE ACTIVATION GUIDE

## 1. Amazon Associates & PA-API 5.0 Activation

### Architecture & Protocol
- **Amazon PA-API 5.0**: Uses AWS SigV4 cryptographic signing (HMAC-SHA256).
- **Distinction**:
  - **Associates Tracking Tag** (e.g. `arikartech-20`, `arikartechuk-21`, `arikartechde-21`) — Publicly visible in affiliate URLs.
  - **PA-API Credentials** (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`) — Secret backend credentials required for real-time item search and lookup.

### Configuration in `backend/.env`:
```env
AMAZON_PAAPI_KEY=your_aws_access_key
AMAZON_PAAPI_SECRET=your_aws_secret_key
AMAZON_TAG_US=arikartech-20
AMAZON_TAG_UK=arikartechuk-21
AMAZON_TAG_DE=arikartechde-21
AMAZON_TAG_FR=arikartechfr-21
AMAZON_TAG_ES=arikarteches-21
AMAZON_TAG_IT=arikartechit-21
AMAZON_TAG_AU=arikartechau-22
```

### Verification & Testing
```bash
# Test Amazon driver connection
php artisan affiliate:test-provider amazon

# Inspect Amazon US retailer configuration
php artisan affiliate:test-retailer amazon-us
```

---

## 2. Awin Network & Create-a-Feed Activation

### Architecture & Protocol
- **Joined Programmes API**: `GET /publishers/{publisherId}/programmes?relationship=joined`
- **Product Datafeed**: Streaming GZIP/CSV ingestion via configured `AWIN_DATAFEED_URL`.

### Configuration in `backend/.env`:
```env
AWIN_PUBLISHER_ID=3053247
AWIN_API_TOKEN=your_oauth_api_token
AWIN_DATAFEED_URL="https://productdata.awin.com/datafeed/download/apikey/..."
```

### Ingestion Commands:
```bash
# Verify Awin connection
php artisan affiliate:test-provider awin

# Bounded dry-run sync for Germany
php artisan automation:sync-market de --limit=10 --dry-run

# Live bounded sync
php artisan automation:sync-market de --limit=20
```

---

## 3. CJ Affiliate (Commission Junction) Activation

### Configuration in `backend/.env`:
```env
CJ_API_TOKEN=your_personal_access_token
CJ_COMPANY_ID=your_company_id
CJ_WEBSITE_ID=your_website_id
```

### Verification:
```bash
php artisan affiliate:test-provider cj
php artisan affiliate:test-retailer bestbuy-us
```

---

## 4. Impact.com Activation

### Configuration in `backend/.env`:
```env
IMPACT_ACCOUNT_SID=your_account_sid
IMPACT_AUTH_TOKEN=your_auth_token
IMPACT_MEDIA_PARTNER_ID=your_media_partner_id
```

### Verification:
```bash
php artisan affiliate:test-provider impact
php artisan affiliate:test-retailer walmart-us
```

---

## 5. TradeDoubler, Rakuten, Partnerize & Direct Feeds

| Network | Required Configuration | Test Command |
| :--- | :--- | :--- |
| **TradeDoubler** | `TRADEDOUBLER_TOKEN`, `TRADEDOUBLER_AFFILIATE_ID` | `php artisan affiliate:test-provider tradedoubler` |
| **Rakuten** | `RAKUTEN_API_TOKEN`, `RAKUTEN_MID`, `RAKUTEN_SITE_ID` | `php artisan affiliate:test-provider rakuten` |
| **Partnerize** | `PARTNERIZE_USER_KEY`, `PARTNERIZE_API_KEY`, `PARTNERIZE_CAMREF` | `php artisan affiliate:test-provider partnerize` |
| **Direct Feed** | `DIRECT_DATAFEED_URL` | `php artisan affiliate:test-provider direct` |
