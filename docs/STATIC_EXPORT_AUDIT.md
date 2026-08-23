# ARIKARTECH — Static Export Audit & Migration Analysis

**Date**: August 23, 2026  
**Auditor**: Principal Full-Stack Architect  
**Objective**: Audit the Next.js 15 public storefront for conversion to a 100% static HTML/CSS/JS export (`output: 'export'`) deployable on Himalayan Host shared hosting with zero Node.js server dependencies.

---

## 1. Audit Matrix of Incompatible / Server-Dependent Features

| Feature / Pattern | File Path | Incompatibility Reason | Required Change | Resolution Layer |
| :--- | :--- | :--- | :--- | :---: |
| **Server Rewrites** | `next.config.mjs` (`rewrites()`) | Server-side URL rewrites (`/sitemap.xml -> /api/sitemap`) require a running Node.js server. | Remove `rewrites()`, configure `output: 'export'`, and generate static `sitemap.xml` / `robots.txt` at build time. | Build-Time / Config |
| **Next.js API Route** | `app/api/out/[offerId]/route.ts` | Dynamic server route handler cannot execute without Node.js runtime. | Delete Next.js proxy route handler. Direct outbound affiliate CTA links straight to Laravel endpoint: `https://api.arikartech.com/api/v1/affiliates/out/{offerId}`. | Laravel API |
| **Server Route Handler for Sitemap** | `app/sitemap.xml/route.ts` | Dynamic XML response generation via `NextResponse` route handler. | Convert to Next.js standard Metadata Route `app/sitemap.ts` (or build-time static generator) which outputs static `out/sitemap.xml`. | Build-Time Static |
| **Server Route Handler for Robots** | `app/robots.txt/route.ts` | Dynamic text response generation via `NextResponse` route handler. | Convert to Next.js standard Metadata Route `app/robots.ts` which outputs static `out/robots.txt`. | Build-Time Static |
| **Dynamic Params Without Static Gen** | `app/[market]/products/[slug]/page.tsx` | Missing `generateStaticParams()` causes build to fail under `output: 'export'`. | Implement `generateStaticParams()` to pre-render canonical product pages across 9 markets. | Build-Time Static |
| **Dynamic Params on Category Pages** | `app/[market]/categories/[slug]/page.tsx` | Missing `generateStaticParams()` for category slugs. | Implement `generateStaticParams()` to pre-render categories across 9 markets. | Build-Time Static |
| **Dynamic Params on Brand Pages** | `app/[market]/brands/[slug]/page.tsx` | Missing `generateStaticParams()` for brand slugs. | Implement `generateStaticParams()` to pre-render brands across 9 markets. | Build-Time Static |
| **Dynamic Market Layouts & Informational Pages** | `app/[market]/layout.tsx`, `about`, `privacy`, `terms`, `disclosure`, `contact` | Dynamic market parameters (`[market]`). | Add `generateStaticParams()` on market segments returning `['us', 'uk', 'de', 'fr', 'es', 'it', 'nl', 'au', 'nz']`. | Build-Time Static |
| **Server-Rendered Search Queries** | `app/[market]/search/page.tsx` | Reading runtime `searchParams` during SSR cannot be statically exported. | Convert search page into a client-rendered component wrapped in `<Suspense>` querying `https://api.arikartech.com/api/v1/products?market=...&q=...`. | Browser-Side / Laravel API |
| **Server-Rendered Comparison Queries** | `app/[market]/compare/page.tsx` | Reading runtime `searchParams: { slugs }` during SSR. | Wrap comparison viewer in `<Suspense>` and read query params client-side via `useSearchParams()`. | Browser-Side / Laravel API |
| **Server Root Redirect** | `app/page.tsx` (`redirect('/us')`) | Server-side 307 redirect via `next/navigation` in root page. | Use client-side immediate redirection with `<meta http-equiv="refresh" content="0;url=/us">` fallback. | Browser-Side / Static |
| **Image Optimization Server** | `next.config.mjs` (`next/image`) | Default Next.js Image Optimization requires an active Node.js server. | Set `images: { unoptimized: true }` in `next.config.mjs`. | Build-Time / Config |
| **Monorepo File Dependency** | `package.json` (`@arikartech/shared`) | Monorepo relative link `file:../shared`. | Verified TypeScript compiles shared contracts directly into JavaScript bundles during build. | Build-Time |
