# ARIKARTECH — Provider Setup & Credentials Guide

This guide details how to configure each authorized affiliate network in production environments without exposing credentials or hardcoding secrets.

---

## 1. Environment Variable Configuration

Add the following environment variables to your production `backend/.env` file:

### A. Awin Publisher Network
```dotenv
AWIN_API_TOKEN=your_awin_oauth_api_token
AWIN_PUBLISHER_ID=your_awin_publisher_id
```

### B. CJ Affiliate (Commission Junction)
```dotenv
CJ_API_TOKEN=your_cj_personal_access_token
CJ_COMPANY_ID=your_cj_company_id
CJ_WEBSITE_ID=your_cj_website_pid
```

### C. Impact (Impact.com)
```dotenv
IMPACT_ACCOUNT_SID=your_impact_account_sid
IMPACT_AUTH_TOKEN=your_impact_auth_token
IMPACT_MEDIA_PARTNER_ID=your_impact_mediapartner_id
```

### D. Amazon Associates & PA-API 5.0 (Deferred)
```dotenv
AMAZON_PAAPI_KEY=your_amazon_access_key
AMAZON_PAAPI_SECRET=your_amazon_secret_key
AMAZON_TAG_US=arikartech-20
AMAZON_TAG_UK=arikartechuk-21
AMAZON_TAG_DE=arikartechde-21
```

---

## 2. Testing Connections in Admin UI
1. Navigate to the Admin Dashboard: `/affiliates/providers`.
2. Click **Test** on the target provider.
3. The platform will query the provider's live endpoint and report status:
   - `Connected` (with roundtrip latency in ms)
   - `Disconnected / Unconfigured`
   - `Invalid Credentials`
   - `Deferred / Not Eligible`

---

## 3. Triggering Controlled Bounded Ingestion
To run a bounded sync batch without exceeding shared hosting memory or API quotas:

```bash
# Ingest up to 25 items from CJ Affiliate in the US market
php artisan automation:ingest-provider --provider=cj --market=us --limit=25 --keywords="Laptops"

# Ingest up to 25 items from Awin in the UK market
php artisan automation:ingest-provider --provider=awin --market=uk --limit=25 --keywords="Smartphones"

# Ingest up to 25 items from Impact in the Germany market
php artisan automation:ingest-provider --provider=impact --market=de --limit=25 --keywords="GPUs"
```
