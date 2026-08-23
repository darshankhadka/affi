# ARIKARTECH — Real Catalog Launch Procedure & Staged Rollout

## 1. Staged Ingestion Strategy
To prevent network throttling, memory spikes, or database bloat on shared hosting, the initial catalog launch follows a strictly controlled 3-stage rollout.

```
┌─────────────────────────────────────────────────────────────┐
│                    STAGE 1: CANARY BATCH                    │
│             25 Items (US Market · CJ Affiliate)             │
│            Verify DTO -> Match -> Price -> Offer            │
└──────────────────────────────┬──────────────────────────────┘
                               │ PASS
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    STAGE 2: SCALE BATCH                     │
│            50 Items (UK Market · Awin Network)              │
│       Verify Currency & Market Isolation (GBP vs USD)       │
└──────────────────────────────┬──────────────────────────────┘
                               │ PASS
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                   STAGE 3: FULL PRODUCTION                  │
│       100 Items (DE Market · Impact / Awin Network)         │
│          Verify Deduplication & Price History Volatility    │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Launch Execution Commands

### Stage 1: CJ Affiliate Canary Ingestion (US Market)
```bash
# Ingest 25 Laptops from CJ in the US Market
php artisan automation:ingest-provider --provider=cj --market=us --limit=25 --keywords="gaming laptop"

# Validate catalog integrity
php artisan catalog:validate
php artisan catalog:quality
```

### Stage 2: Awin Network Expansion (UK Market)
```bash
# Ingest 50 Smartphones from Awin in the UK Market
php artisan automation:ingest-provider --provider=awin --market=uk --limit=50 --keywords="smartphones"

# Verify multi-market isolation
php artisan catalog:seo-audit
```

### Stage 3: Direct Brand & Component Expansion (DE Market)
```bash
# Ingest 50 GPUs from Impact in the Germany Market
php artisan automation:ingest-provider --provider=impact --market=de --limit=50 --keywords="RTX graphics card"

# Check full catalog health
php artisan catalog:health
php artisan catalog:stats
```
