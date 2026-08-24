# ARIKARTECH Affiliate Engine - Architecture Audit & Hardening Report

## Executive Summary

This audit covers the complete affiliate ingestion architecture for the ArikarTech product comparison engine. The audit was triggered by an incident where Awin's publisher-wide configured feed URL was incorrectly overridden by an advertiser-specific feed URL constructor, causing HTTP 404 errors for BlazeVideo DE (advertiser 25962) and Geekbuying DE (advertiser 57897).

**Root Cause**: The `AwinDatafeedService::getFeedUrl()` method unconditionally constructs `/mid/{advertiserId}` feed URLs, ignoring the configured `AWIN_DATAFEED_URL` which is a publisher-wide feed. This reveals a deeper architectural problem: **no feed strategy abstraction** exists to handle different feed architectures.

---

## 1. Current Architecture Overview

### 1.1 Provider Flow

```
AffiliateRegistry (8 providers)
    └── AwinProvider
           ├── getJoinedProgrammes() → Awin Publisher API
           ├── searchProducts() 
           │     └── foreach programme → getFeedUrl() → streamFeedRecords()
           ├── normalizeAwinItem()
           └── generateAffiliateUrl()
```

### 1.2 Ingestion Flow

```
AwinProvider::searchProducts()
    → AwinDatafeedService::getFeedUrl(advertiserId)  // BUG: ignores AWIN_DATAFEED_URL
    → AwinDatafeedService::streamFeedRecords()
    → AwinProvider::normalizeAwinItem()
    → ProductIngestionService::ingest()
    → ProductMatchingService::match()
    → Retailer resolution (domain-based, dynamic creation)
    → Product/Offer creation
    → BestPriceService::recordPriceHistory() + recalculate()
```

### 1.3 Command Structure

| Command | Purpose | Scope |
|---------|---------|-------|
| `affiliate:sync {retailer}` | Retailer-level sync (requires existing Retailer) | Single retailer |
| `automation:sync-retailer` | Wrapper for `affiliate:sync` | Single retailer |
| `automation:ingest-provider` | Provider-level catalog ingestion | Publisher-wide / all advertisers |

---

## 2. Critical Defects Identified

### 2.1 Awin Feed Architecture (CRITICAL)

**File**: `app/Services/Affiliate/AwinDatafeedService.php:200-235`

**Problem**: `getFeedUrl()` unconditionally builds advertiser-specific URLs:
```php
return "https://productdata.awin.com/datafeed/download/"
    . "apikey/{$key}/"
    . "language/{$language}/"
    . "mid/{$advertiserId}/"  // Forces advertiser-specific feed
    ...
```

**Impact**: 
- Publisher-wide feed (`AWIN_DATAFEED_URL`) is ignored
- HTTP 404 for advertisers without independent feeds
- Same feed downloaded N times for N advertisers

**Required Feed Architectures**:
- **Architecture A**: Publisher-wide configured feed (`AWIN_DATAFEED_URL`)
- **Architecture B**: Advertiser-specific feed (`/mid/{advertiserId}`)
- **Architecture C**: Future Awin API/datafeed mechanism

### 2.2 Duplicate Feed Downloads (HIGH)

**File**: `app/Services/Affiliate/AwinProvider.php:258-306`

```php
foreach ($matchingProgrammes as $prog) {
    $feedUrl = $this->datafeedService->getFeedUrl($prog['id'], $market);
    $streamResult = $this->datafeedService->streamFeedRecords($feedUrl, ...);
}
```

If `AWIN_DATAFEED_URL` is publisher-wide, this downloads the **same feed once per advertiser**.

### 2.3 CSV Parsing Vulnerability (HIGH)

**File**: `app/Services/Affiliate/AwinDatafeedService.php:375-381`

```php
while (($pos = strpos($buffer, "\n")) !== false) {
    $line = substr($buffer, 0, $pos);
    // ...
}
```

**Problem**: Line-based splitting breaks on legitimate CSV fields containing embedded newlines (`\n` inside quoted fields).

**Example vulnerable CSV**:
```
"Product Name","Description with
embedded newline",19.99
```

### 2.4 Retailer Deduplication Flaws (HIGH)

**File**: `app/Services/Ingestion/ProductIngestionService.php:162-181`

```php
$normalizedDomain = preg_replace('/^www\./i', '', strtolower(trim((string) $offerDto->retailerDomain)));
$retailer = Retailer::where('domain', $normalizedDomain)->first();
if (!$retailer) {
    $baseSlug = Str::slug($offerDto->retailerName);
    $slug = $baseSlug;
    if (Retailer::where('slug', $slug)->exists()) {
        $slug = $baseSlug . '-' . substr(md5($normalizedDomain), 0, 4);  // Unstable!
    }
    // Creates new retailer with unstable code/slug
}
```

**Problems**:
- Domain-only matching: `shop.example.com` ≠ `example.com` ≠ `www.example.de`
- Slug uses `md5(domain)[:4]` - changes when domain formatting changes
- `code` field mirrors unstable slug
- No use of `affiliate_program_id` (Awin advertiser ID) for stable identity

### 2.5 Offer Upsert Uniqueness Bug (HIGH)

**File**: `app/Services/Ingestion/ProductIngestionService.php:184-190`

```php
$offer = Offer::updateOrCreate(
    [
        'product_id' => $product->id,
        'retailer_id' => $retailer->id,
        'market_id' => $targetMarket->id,
        'sku' => $offerDto->sku,  // Can be NULL!
    ],
    [...]
);
```

**Problem**: If `sku` is NULL, database unique constraint behavior varies - can create duplicates.

### 2.6 Product Creation Category Fallback (MEDIUM)

**File**: `app/Services/Ingestion/ProductIngestionService.php:78-81`

```php
$category = Category::where('is_active', true)->orderBy('display_order')->first()
    ?? Category::first();  // DANGEROUS: assumes ID 1 exists
```

### 2.7 Slug Generation Race Condition (MEDIUM)

**File**: `app/Services/Ingestion/ProductIngestionService.php:84-89`

```php
while (Product::where('slug', $slug)->exists()) {
    $slug = "{$originalSlug}-" . $counter++;
}
```

**Problem**: Not atomic - concurrent ingestion can create duplicates.

### 2.8 Secret Leakage in Logging (CRITICAL)

**Files**: Multiple
- `AwinDatafeedService::streamFeedRecords()`: Returns full error body in response
- `AwinProvider::testConnection()`: Logs response body on error
- `IngestProviderCommand`: Stores exception trace in `AutomationJob.error_log`
- No secret redaction for `AWIN_API_TOKEN`, `AWIN_DATAFEED_API_KEY`, authenticated URLs

### 2.9 Lock Expiry During Long Feeds (HIGH)

**File**: `app/Console/Commands/IngestProviderCommand.php:59`

```php
$lock = Cache::lock($lockKey, 240);  // 4 minutes
```

**Problem**: Large feeds can exceed 240 seconds → lock expires → concurrent cron starts duplicate ingestion.

### 2.10 Market/Language Conflation (MEDIUM)

**File**: `app/Services/Affiliate/AwinDatafeedService.php:217-221`

```php
$language = in_array($marketCode, ['gb', 'uk', 'ie'], true)
    ? 'en'
    : $marketCode;
```

**Problem**: Assumes country code = language. False for AT/CH/BE/IE.

### 2.11 BestPrice Cascade Delete Risk (MEDIUM)

**File**: `database/migrations/2026_08_23_050004_create_affiliates_and_offers_tables.php:98`

```php
$table->foreignId('best_offer_id')->constrained('offers')->cascadeOnDelete();
```

**Problem**: Deleting an offer cascades to delete the best_price record.

---

## 3. Performance Risks

| Risk | Location | Impact |
|------|----------|--------|
| N+1 queries in ProductIngestionService | Brand/Category/Market/Currency resolution per item | High |
| No feed caching | AwinProvider::searchProducts() | High (duplicate downloads) |
| Unbounded memory in ZIP handling | AwinDatafeedService::handleZipStream() | Medium |
| No HTTP connection pooling | All providers | Medium |

---

## 4. Security Risks

| Risk | Location | Severity |
|------|----------|----------|
| API tokens in exception messages | AwinProvider, AwinDatafeedService | Critical |
| Authenticated URLs in job error_log | IngestProviderCommand | Critical |
| No input validation on feed data | AwinDatafeedService, normalizeAwinItem | High |
| Stored XSS via merchant descriptions | ProductIngestionService | Medium |

---

## 5. Compatibility Risks

| Risk | Impact |
|------|--------|
| No unique constraint on `retailers.domain` | Duplicate retailers |
| No unique constraint on `retailers.affiliate_program_id` per provider | Duplicate retailer identities |
| `offers.sku` nullable without unique constraint | Duplicate offers |
| `best_prices.best_offer_id` cascade delete | Data loss on offer deletion |

---

## 6. Architecture Improvement Plan

### 6.1 Feed Strategy Abstraction (Priority 1)

Create `FeedSource` abstraction:

```php
interface FeedSource {
    public function getType(): string;           // 'publisher_wide' | 'advertiser_specific' | 'api'
    public function getUrl(): string;
    public function getAdvertiserIds(): array;   // For filtering
    public function supportsMarket(Market $market): bool;
    public function getCredentials(): array;     // Never logged
}

class PublisherWideFeedSource implements FeedSource { ... }
class AdvertiserSpecificFeedSource implements FeedSource { ... }
```

### 6.2 Provider Capability Matrix

| Provider | API | Publisher Feed | Advertiser Feed | Search | Deep Link | Price Tracking |
|----------|-----|----------------|-----------------|--------|-----------|----------------|
| Awin     | ✓   | ✓              | ✓               | ✓      | ✓         | Partial        |
| Amazon   | ✓   | -              | -               | ✓      | ✓         | ✓              |
| CJ       | ✓   | -              | -               | ✓      | ✓         | -              |
| Impact   | ✓   | -              | -               | -      | ✓         | -              |
| Direct   | -   | ✓              | -               | -      | ✓         | -              |

### 6.3 Retailer Identity Strategy

```
Stable Identity Keys (in priority order):
1. affiliate_provider_id + affiliate_program_id (Awin advertiser ID)
2. normalized_root_domain (e.g., "blazevideos.de" from "shop.blazevideos.de")
3. slug (derived from stable identity, not merchant_name)
```

---

## 7. Files Requiring Changes

### Core Architecture
- `app/Services/Affiliate/AwinDatafeedService.php` - Feed strategy, CSV parser, secret redaction
- `app/Services/Affiliate/AwinProvider.php` - searchProducts(), feed strategy integration
- `app/Services/Ingestion/ProductIngestionService.php` - Retailer resolution, offer upsert, product creation
- `app/Services/Matching/ProductMatchingService.php` - Matching hierarchy
- `app/Services/Pricing/BestPriceService.php` - Cascade delete fix

### Commands
- `app/Console/Commands/IngestProviderCommand.php` - Lock strategy, job status accuracy
- `app/Console/Commands/AffiliateSyncCommand.php` - Dry-run semantics
- New: `app/Console/Commands/AwinFeedDiagnosticCommand.php` - Configuration diagnostics

### Database (New Migrations)
- Unique constraint on `retailers.domain` (after deduplication)
- Unique constraint on `retailers.affiliate_provider_id, retailers.affiliate_program_id`
- Unique constraint on `offers.product_id, offers.retailer_id, offers.market_id, offers.sku` (sku not null)
- Change `best_prices.best_offer_id` to `nullOnDelete()`
- Add `retailers.canonical_domain` column

### Tests (New/Updated)
- Awin publisher-wide feed tests
- CSV quoted newline tests
- Retailer deduplication tests
- Secret redaction tests
- Lock renewal tests

---

## 8. Acceptance Criteria for Fixes

### Awin Feed
- [ ] `AWIN_DATAFEED_URL` is respected as publisher-wide feed
- [ ] Feed downloaded once per ingestion run regardless of advertiser count
- [ ] Advertiser filtering happens *after* feed parsing
- [ ] `/mid/{advertiserId}` feed still works when configured

### Retailer Deduplication
- [ ] Same Awin advertiser ID → same Retailer record
- [ ] Domain variations (www, subdomain, TLD) → same Retailer
- [ ] Stable `code` and `slug` based on advertiser ID

### Security
- [ ] No API tokens in logs, exceptions, job error_log
- [ ] Authenticated URLs sanitized before storage
- [ ] Feed data validated before persistence

### Database
- [ ] No duplicate retailers possible
- [ ] No duplicate offers possible
- [ ] BestPrice survives offer deletion

### Operations
- [ ] Lock renewal for long feeds
- [ ] Dry-run truly prevents all writes
- [ ] Job status accurately reflects provider failures