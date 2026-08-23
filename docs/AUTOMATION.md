# ARIKARTECH CPU-Safe Automation Architecture

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

## 2. Configurable Limits (`config/automation.php`)
```php
return [
    'max_items_per_run' => (int) env('AUTOMATION_MAX_ITEMS_PER_RUN', 100),
    'max_runtime_seconds' => (int) env('AUTOMATION_MAX_RUNTIME_SECONDS', 240),
    'max_api_requests' => (int) env('AUTOMATION_MAX_API_REQUESTS_PER_RUN', 50),
    'max_retries' => (int) env('AUTOMATION_MAX_RETRIES', 3),
    'chunk_size' => (int) env('AUTOMATION_CHUNK_SIZE', 25),
];
```

---

## 3. Automation States & Telemetry
Every automated batch records an entry in the `automation_jobs` table with:
- `status`: `pending`, `processing`, `completed`, `failed`, `skipped`
- `processed_items` & `failed_items`
- `cpu_time_ms` (elapsed millisecond CPU duration)
- `memory_peak_bytes` (peak RAM utilized)
- `error_log` (truncated stack traces of failures)

---

## 4. Cron Configuration
On shared hosting (cPanel / DirectAdmin / Crontab), schedule the standard Laravel Scheduler:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```
Or run the bounded price refresh command directly every 30 minutes:
```bash
*/30 * * * * cd /path/to/backend && php artisan automation:refresh-prices >> /dev/null 2>&1
```
