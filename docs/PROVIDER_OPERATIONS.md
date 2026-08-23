# ARIKARTECH — Provider Operations & Failure Recovery

## 1. Multi-Network Integration Health

ARIKARTECH implements drivers for 4 premier networks:
1. **CJ Affiliate** (`cj`): GraphQL API with personal access tokens.
2. **Awin Publisher Network** (`awin`): REST API with OAuth bearer tokens.
3. **Impact.com** (`impact`): REST API with Basic Auth.
4. **Amazon PA-API 5.0** (`amazon`): Maintained in **DEFERRED / NOT ELIGIBLE** status.

---

## 2. Failure Recovery & Circuit Breaking
To prevent catalog corruption during external network outages:
1. **Zero Destructive Deletions**: If a provider API returns HTTP 500, existing offers and canonical products are **never** deleted.
2. **Retain Last Known State**: Prices and availability remain in their last verified state until scheduled freshness policies evaluate them.
3. **Exponential Error Backoff**: Repeated connection errors increment `error_count` and defer retries exponentially ($2^{\text{error\_count}}$ hours).
4. **Zero Credential Exposure**: API keys, auth tokens, and client secrets are encrypted in the database and never output in logs, frontend responses, or error messages.
