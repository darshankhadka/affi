# ARIKARTECH — Geo Routing & Market Resolution Architecture

This document details the multi-market detection, currency isolation, and geo-routing mechanisms in ARIKARTECH.

---

## 1. Resolution Hierarchy

When a user accesses the platform, the target market is determined using the following strict priority:

```
1. EXPLICIT URL / USER SELECTION (e.g. /de/products/..., market cookie)
        ↓
2. CLOUDFLARE / CDN IP GEOLOCATION HEADERS (CF-IPCountry, X-Country-Code)
        ↓
3. BROWSER LOCALE / HTTP ACCEPT-LANGUAGE HEADER
        ↓
4. DEFAULT FALLBACK MARKET (us)
```

Client-supplied market codes are never trusted blindly; they are validated against active database market records in `App\Models\Market`.

---

## 2. Market Architecture Matrix

ARIKARTECH supports 35 global market routing codes including:
- **North America**: United States (`us`), Canada (`ca`)
- **United Kingdom & Europe**: United Kingdom (`gb` / `uk`), Germany (`de`), France (`fr`), Spain (`es`), Italy (`it`), Denmark (`dk`), Netherlands (`nl`), Sweden (`se`), Norway (`no`), Finland (`fi`), Austria (`at`), Belgium (`be`), Switzerland (`ch`), Ireland (`ie`), Poland (`pl`), Czechia (`cz`), Portugal (`pt`), Greece (`gr`), Romania (`ro`), Hungary (`hu`)
- **Asia-Pacific & Global**: Australia (`au`), New Zealand (`nz`), Japan (`jp`), Singapore (`sg`), India (`in`), Brazil (`br`), Mexico (`mx`), United Arab Emirates (`ae`), Saudi Arabia (`sa`), South Africa (`za`), Global/International fallback (`int` / `us`)

---

## 3. Product & Price Isolation

- **One Canonical Product**: Product identity is global and persistent.
- **Market-Specific Offers**: Each offer belongs strictly to a `market_id` and `currency_id`.
- **Best Price Calculation**: Calculated strictly within the user's market context. An EU price (in EUR) is never presented as a US price (in USD).
