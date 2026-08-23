# ARIKARTECH — Affiliate Network Production Activation Checklist

Use this checklist prior to flipping production networks from testing to live synchronization.

---

## 1. Network Application & Approval Checklist

- [ ] **Awin (Publisher ID + API Token)**:
  - Join Currys, MediaMarkt, Samsung UK/EU, Dell UK, HP programs.
  - Test connection in Admin: `/affiliates/providers`.
  - Verify tracking link generates valid `awin1.com/cread.php?awinmid=...&awinaffid=...&clickref=...`.

- [ ] **CJ Affiliate (Personal Access Token + Company ID + Website ID)**:
  - Apply to Dell US, Best Buy US, Samsung US, Lenovo US, Newegg programs.
  - Test connection in Admin.
  - Verify tracking link generates valid `anrdoezrs.net/click-...-...?sid=...`.

- [ ] **Impact.com (Account SID + Auth Token + Media Partner ID)**:
  - Apply to Lenovo Direct, Razer Store, ASUS, Western Digital programs.
  - Test connection in Admin.
  - Verify tracking link generates valid `impact.sjv.io/c/.../.../.../?subId1=...`.

- [ ] **Amazon Associates (Deferred)**:
  - Kept in `DEFERRED / NOT ELIGIBLE` until minimum qualifying sales threshold is reached via Awin/CJ/Impact.

---

## 2. Ingestion & Outbound Click Verification

- [ ] Run first bounded sync in target market:
  ```bash
  php artisan automation:ingest-provider --provider=cj --market=us --limit=10 --keywords="Laptops"
  ```
- [ ] Inspect canonical product created in Admin: `/catalog/products`.
- [ ] Verify product page displays on public frontend: `/us/products/{slug}`.
- [ ] Click "BUY AT BEST PRICE" and verify redirect to merchant destination with sub-tracking parameter.
- [ ] Inspect Admin click log: `/analytics/clicks`.
