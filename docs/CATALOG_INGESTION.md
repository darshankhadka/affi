# ARIKARTECH — Catalog Ingestion & Ingestion Safety Guide

This guide details the high-scale, memory-safe, and bounded ingestion architecture powering ARIKARTECH across CJ, Awin, Impact, and Amazon.

---

## 1. Core Principles

1. **Absolute Data Integrity**: We never invent product information, fabricate prices, or simulate offers.
2. **Canonical Product Identity**: Products are identified deterministically by GTIN, EAN, UPC, ASIN, MPN, and brand + model. Multiple retailers with the same identifiers attach to the single canonical product record.
3. **Approval Gate**: For network providers (Awin, CJ), only offers belonging to approved programmes (`AffiliateProgramme::where('status', 'approved')`) are created or redirected.
4. **Bounded Execution**: Ingestion batches process in bounded chunks (10–50 items) protected by mutex locks with TTL caps, keeping peak memory under 64MB.

---

## 2. Ingestion Commands

### Awin Ingestion & Diagnostics
```bash
# Diagnostic & connection check
php artisan affiliate:awin-diagnostic

# List approved programmes
php artisan affiliate:awin-programmes

# Test streaming feed parsing (dry-run)
php artisan affiliate:test-awin-feed --advertiser=25962 --market=de --limit=10

# Run bounded ingestion
php artisan automation:ingest-provider --provider=awin --market=de --limit=25 --keywords="Electronics"
```

### CJ Affiliate Ingestion & Diagnostics
```bash
# Diagnostic & connection check
php artisan affiliate:cj-diagnostic

# Run bounded ingestion (filters partnerStatus: JOINED)
php artisan automation:ingest-provider --provider=cj --market=us --limit=25 --keywords="Laptop"
```

### Amazon Manual Import (Mode 1)
```bash
# Validate URL & ASIN
php artisan affiliate:import-amazon "https://www.amazon.com/dp/B0CX23V2ZP" --dry-run

# Import product offer
php artisan affiliate:import-amazon "https://www.amazon.com/dp/B0CX23V2ZP" \
  --name="Apple MacBook Air 13 M3" \
  --price=1099.00 \
  --market=us \
  --brand="Apple" \
  --category="laptops"
```

---

## 3. Ingestion Lifecycle Pipeline

```
Provider Feed / API
        ↓
NormalizedProductDTO & NormalizedOfferDTO
        ↓
Canonical Match (GTIN > EAN > UPC > ASIN > MPN > Brand+Model)
        ↓
Retailer Identity Resolution (domain deduplication)
        ↓
Affiliate Programme Approval Check (status: approved)
        ↓
Database Persistence (Product, Offer, Specification, Image)
        ↓
Recalculate Best Price & Record Price History
```
