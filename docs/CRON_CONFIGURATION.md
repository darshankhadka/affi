# ARIKARTECH — Production Cron Configuration & Bounded Automation

## 1. System Crontab Entry
Add this single entry to your server crontab (`crontab -e`):

```cron
* * * * * cd /var/www/arikartech/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 2. Schedule Topology & CPU Safety Matrix

| Schedule | Command | Bounded Limits | Runtime Limit | Mutex Lock Key |
| :--- | :--- | :---: | :---: | :--- |
| **Every 15 min** | `automation:ingest-provider --provider=cj --market=us --limit=25` | 25 items | 240s | `automation:ingest:cj:us` |
| **Every 30 min** | `automation:ingest-provider --provider=awin --market=uk --limit=25` | 25 items | 240s | `automation:ingest:awin:uk` |
| **Hourly** | `automation:ingest-provider --provider=impact --market=us --limit=25` | 25 items | 240s | `automation:ingest:impact:us` |
| **Hourly** | `automation:refresh-prices --batch=25` | 25 items | 180s | `automation:refresh_prices` |
| **Hourly** | `pricing:recalculate` | All active markets | 180s | `pricing:recalculate` |
| **Daily (03:00)** | Data quality audit (`DataQualityService::audit()`) | Full catalog | 240s | `quality:audit` |

---

## 3. Guarantees on Shared Hosting
1. **Never Spawns Daemons**: Tasks run as bounded one-shot scripts and immediately terminate.
2. **Mutual Exclusion**: `withoutOverlapping(240)` prevents process accumulation if an external API is slow.
3. **RAM Ceiling**: Memory is capped strictly below **32MB RAM**.
