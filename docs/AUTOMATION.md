# ARIKARTECH CPU-Safe Automation Architecture (Phase 2)

## 1. Principles of CPU Safety on Shared Hosting
Shared hosting environments strictly constrain CPU burst time, concurrent processes, and available RAM. Uncontrolled batch processing or infinite worker loops will saturate shared CPU quotas.

To guarantee hosting stability:
1. **Bounded Batches**: Every automation run processes at most `MAX_ITEMS_PER_RUN` items.
2. **Hard Runtime Limits**: Every run halts gracefully after `MAX_RUNTIME_SECONDS` (default: 240s) even if items remain in the queue.
3. **Atomic Mutual Exclusion**: Process locking via `Cache::lock` prevents overlapping cron jobs.
4. **Resumable State**: Stale offers are ordered by `last_checked_at` and `next_check_at`, ensuring subsequent runs naturally resume from the oldest unchecked items.
5. **Exponential Backoff**: Transient retailer or API errors increment an `error_count` and defer the next check exponentially ($2^{\text{error\_count}}$ hours).
6. **Chunked Memory Footprint**: Items are loaded and updated in micro-chunks of 25 items to keep peak RAM below 32MB.

---

## 2. Ingestion Commands & Batch Operations

### Bounded Provider Ingestion
```bash
php artisan automation:ingest-provider --provider=amazon --market=us --limit=25 --keywords="Laptops"
```

### Bounded Price & Availability Refresh
```bash
php artisan automation:refresh-prices
```

### Full Best Price Index Materialization
```bash
php artisan pricing:recalculate-all
```

---

## 3. Automation Job Telemetry & Logging
Every automated batch registers an immutable record in `automation_jobs`:
- `batch_type`: `price_refresh`, `provider_ingestion`, `recalculate_all`
- `status`: `pending`, `processing`, `completed`, `failed`, `skipped`
- `total_items`, `processed_items`, `failed_items`
- `cpu_time_ms` (elapsed millisecond CPU duration)
- `memory_peak_bytes` (peak RAM utilized)
- `error_log` (truncated stack traces of failures)
- `metadata` (provider, market, keywords, limits)
