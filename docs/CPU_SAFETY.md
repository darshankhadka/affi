# ARIKARTECH — CPU Safety & Shared Hosting Architecture

## 1. Hosting Environment Constraints
Shared hosting environments strictly limit CPU time, concurrent processes, and memory ceilings. Unbounded batch jobs or persistent worker daemons lead to host throttling or process termination.

---

## 2. Safeguard Enforcement Rules
1. **Bounded Batching**: Every automation command enforces `MAX_ITEMS_PER_RUN` (default: 50 items).
2. **Hard Runtime Timeout**: Commands monitor execution time and halt gracefully before reaching `MAX_RUNTIME_SECONDS` (default: 240s).
3. **Atomic Mutual Exclusion**: `Cache::lock("automation:lock:{job_type}:{market}", 240)` prevents overlapping cron executions.
4. **Memory Footprint Ceiling**: Processing in micro-chunks of 25 records guarantees memory consumption remains below **32MB RAM**.
5. **Exponential Error Backoff**: Failed external network calls increment `error_count` and defer retries exponentially ($2^{\text{error\_count}}$ hours).
6. **Zero Daemon Requirement**: No Redis, Elasticsearch, Supervisor, or Horizon daemons required.
