# ARIKARTECH — RETAILER ACTIVATION MATRIX (35 MARKETS / 105 MERCHANTS)

## 1. Locked Status Taxonomy
- `connected`: Active API/feed credentials configured and verified against live provider endpoints.
- `pending`: Application submitted to network or waiting for advertiser manager approval.
- `application_required`: Merchant requires publisher application in network portal before feed/links are unlocked.
- `not_configured`: Provider driver is supported, but API keys are not yet entered in `.env`.
- `error`: Authentication failure, expired token, or rate limit backoff.
- `disabled`: Merchant manually paused by administrator.

---

## 2. 105 Locked Merchants Activation Status

| Market | Priority Retailer | Domain | Provider Code | Network / Type | Required Key | Truthful Status |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **US** | Amazon.com | `amazon.com` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **US** | Walmart | `walmart.com` | `impact` | Impact.com | `IMPACT_ACCOUNT_SID` | `not_configured` |
| **US** | Best Buy | `bestbuy.com` | `cj` | CJ Affiliate | `CJ_API_TOKEN` | `not_configured` |
| **CA** | Amazon.ca | `amazon.ca` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **CA** | Best Buy Canada | `bestbuy.ca` | `cj` | CJ Affiliate | `CJ_API_TOKEN` | `not_configured` |
| **CA** | Walmart Canada | `walmart.ca` | `impact` | Impact.com | `IMPACT_ACCOUNT_SID` | `not_configured` |
| **GB** | Amazon.co.uk | `amazon.co.uk` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **GB** | Currys | `currys.co.uk` | `awin` | Awin (ID: 1599) | `AWIN_API_TOKEN` | `application_required` |
| **GB** | Argos | `argos.co.uk` | `cj` | CJ Affiliate | `CJ_API_TOKEN` | `not_configured` |
| **DE** | Amazon.de | `amazon.de` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **DE** | MediaMarkt | `mediamarkt.de` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **DE** | Cyberport | `cyberport.de` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **FR** | Amazon.fr | `amazon.fr` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **FR** | Fnac | `fnac.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **FR** | Cdiscount | `cdiscount.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NL** | Amazon.nl | `amazon.nl` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **NL** | Coolblue | `coolblue.nl` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NL** | bol | `bol.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **ES** | Amazon.es | `amazon.es` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **ES** | MediaMarkt | `mediamarkt.es` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **ES** | PcComponentes | `pccomponentes.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IT** | Amazon.it | `amazon.it` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **IT** | MediaWorld | `mediaworld.it` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IT** | Unieuro | `unieuro.it` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **DK** | Proshop | `proshop.dk` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **DK** | Elgiganten | `elgiganten.dk` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **DK** | POWER | `power.dk` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SE** | Amazon.se | `amazon.se` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **SE** | Elgiganten | `elgiganten.se` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SE** | Webhallen | `webhallen.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **PL** | Amazon.pl | `amazon.pl` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **PL** | Media Expert | `mediaexpert.pl` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **PL** | x-kom | `x-kom.pl` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CZ** | Alza | `alza.cz` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CZ** | Datart | `datart.cz` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CZ** | CZC | `czc.cz` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **AT** | Amazon.de | `amazon.de` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **AT** | MediaMarkt AT | `mediamarkt.at` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **AT** | e-tec | `e-tec.at` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **BE** | Amazon.com.be | `amazon.com.be` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **BE** | Coolblue BE | `coolblue.be` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **BE** | MediaMarkt BE | `mediamarkt.be` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IE** | Amazon.co.uk | `amazon.co.uk` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **IE** | Currys IE | `currys.ie` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IE** | Harvey Norman | `harveynorman.ie` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **PT** | Amazon.es | `amazon.es` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **PT** | Worten | `worten.pt` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **PT** | FNAC PT | `fnac.pt` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **FI** | Verkkokauppa | `verkkokauppa.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **FI** | Gigantti | `gigantti.fi` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **FI** | Power FI | `power.fi` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **BG** | eMAG BG | `emag.bg` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **BG** | Technopolis | `technopolis.bg` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **BG** | Technomarket | `technomarket.bg` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **HR** | eKupi | `ekupi.hr` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **HR** | Links | `links.hr` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **HR** | Instar | `instar-informatika.hr`| `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CY** | Electroline | `electroline.com.cy`| `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CY** | Stephanis | `stephanis.com.cy` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CY** | Public Cyprus | `public.cy` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **EE** | 1a.ee | `1a.ee` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **EE** | Euronics EE | `euronics.ee` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **EE** | Arvutitark | `arvutitark.ee` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **GR** | Skroutz | `skroutz.gr` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **GR** | Public GR | `public.gr` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **GR** | Plaisio | `plaisio.gr` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **HU** | eMAG HU | `emag.hu` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **HU** | Alza HU | `alza.hu` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **HU** | MediaMarkt HU | `mediamarkt.hu` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LV** | 1a.lv | `1a.lv` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LV** | RD Electronics| `rdveikals.lv` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LV** | Euronics LV | `euronics.lv` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LT** | Varle | `varle.lt` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LT** | 1a.lt | `1a.lt` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LT** | Topocentras | `topocentras.lt` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **LU** | Amazon.de (LU)| `amazon.de` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **LU** | Amazon.fr (LU)| `amazon.fr` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **LU** | MediaMarkt LU | `mediamarkt.lu` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **MT** | Scan Malta | `scanmalta.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **MT** | Forestals | `forestals.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **MT** | Klikk | `klikk.com.mt` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **RO** | eMAG RO | `emag.ro` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **RO** | Altex | `altex.ro` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **RO** | PC Garage | `pcgarage.ro` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SK** | Alza SK | `alza.sk` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SK** | NAY | `nay.sk` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SK** | Datart SK | `datart.sk` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SI** | Mimovrste | `mimovrste.com` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SI** | Big Bang | `bigbang.si` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **SI** | Harvey Norman | `harveynorman.si` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NO** | Elkjøp | `elkjop.no` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NO** | Komplett | `komplett.no` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NO** | Power NO | `power.no` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CH** | Digitec | `digitec.ch` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CH** | Brack | `brack.ch` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **CH** | Interdiscount | `interdiscount.ch`| `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IS** | ELKO | `elko.is` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IS** | Origo | `origo.is` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **IS** | Tölvutek | `tolvutek.is` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **AU** | Amazon.com.au | `amazon.com.au` | `amazon` | PA-API 5.0 | `AMAZON_PAAPI_KEY` | `not_configured` |
| **AU** | JB Hi-Fi | `jbhifi.com.au` | `impact` | Impact.com | `IMPACT_ACCOUNT_SID` | `not_configured` |
| **AU** | Officeworks | `officeworks.com.au`| `impact`| Impact.com | `IMPACT_ACCOUNT_SID` | `not_configured` |
| **NZ** | PB Tech | `pbtech.co.nz` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NZ** | Noel Leeming | `noelleeming.co.nz`| `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
| **NZ** | Mighty Ape | `mightyape.co.nz` | `awin` | Awin | `AWIN_API_TOKEN` | `application_required` |
