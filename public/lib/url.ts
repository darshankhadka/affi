/**
 * ARIKARTECH — Centralized URL Builder
 *
 * All public-facing URL construction must go through this module.
 * Never concatenate product/category/brand URLs ad-hoc across components.
 *
 * Architecture:
 *   SITE_URL  = https://arikartech.com          (public storefront — for canonical, OG, sitemap)
 *   API_URL   = https://api.arikartech.com/api/v1  (backend API — for data fetching only)
 *
 * The two MUST NEVER be confused.
 */

// ---------------------------------------------------------------------------
// Base URL helpers
// ---------------------------------------------------------------------------

/** Public storefront URL — use for canonical, hreflang, Open Graph, sitemap links */
export function getSiteUrl(): string {
  return (
    process.env.NEXT_PUBLIC_SITE_URL || 'https://arikartech.com'
  ).replace(/\/+$/, '');
}

/** API base URL — use ONLY for data fetching, NEVER in canonical/OG/sitemap */
export function getApiUrl(): string {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname;
    if (host === 'localhost' || host === '127.0.0.1') {
      const configured = process.env.NEXT_PUBLIC_API_URL;
      if (!configured || configured.includes('arikartech.com')) {
        return 'http://127.0.0.1:8000/api/v1';
      }
    }
  }

  return (
    process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1'
  ).replace(/\/+$/, '');
}

// ---------------------------------------------------------------------------
// Product URLs
// ---------------------------------------------------------------------------

/**
 * Build the canonical URL for a product page.
 *
 * @example
 *   buildProductUrl('us', 'sony-wh-1000xm5') → 'https://arikartech.com/us/products/sony-wh-1000xm5'
 */
export function buildProductUrl(market: string, slug: string): string {
  return `${getSiteUrl()}/${market}/products/${slug}`;
}

/**
 * Build the relative path for internal Next.js <Link href=...> navigation.
 *
 * @example
 *   productPath('us', 'sony-wh-1000xm5') → '/us/products/sony-wh-1000xm5'
 */
export function productPath(market: string, slug: string): string {
  return `/${market}/products/${slug}`;
}

// ---------------------------------------------------------------------------
// Market URLs
// ---------------------------------------------------------------------------

export function buildMarketUrl(market: string): string {
  return `${getSiteUrl()}/${market}`;
}

export function marketPath(market: string): string {
  return `/${market}`;
}

// ---------------------------------------------------------------------------
// Category URLs
// ---------------------------------------------------------------------------

export function buildCategoryUrl(market: string, slug: string): string {
  return `${getSiteUrl()}/${market}/categories/${slug}`;
}

export function categoryPath(market: string, slug: string): string {
  return `/${market}/categories/${slug}`;
}

// ---------------------------------------------------------------------------
// Brand URLs
// ---------------------------------------------------------------------------

export function buildBrandUrl(market: string, slug: string): string {
  return `${getSiteUrl()}/${market}/brands/${slug}`;
}

export function brandPath(market: string, slug: string): string {
  return `/${market}/brands/${slug}`;
}

// ---------------------------------------------------------------------------
// Affiliate redirect URL (goes through the API, not the frontend)
// ---------------------------------------------------------------------------

export function buildAffiliateUrl(offerId: number | string): string {
  return `${getApiUrl()}/affiliates/out/${offerId}`;
}
