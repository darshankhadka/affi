/**
 * ARIKARTECH API Client
 *
 * All data fetching from the Laravel backend goes through this module.
 * For URL construction (canonical, sitemap, OG) use @/lib/url instead.
 */

// Re-export URL utilities for backward compatibility and convenience
export { getSiteUrl, getApiUrl, buildProductUrl, productPath, buildAffiliateUrl } from './url';

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

function apiBase(): string {
  if (typeof window !== 'undefined') {
    const host = window.location.hostname;
    if (host === 'localhost' || host === '127.0.0.1') {
      const configured = process.env.NEXT_PUBLIC_API_URL;
      if (!configured || configured.includes('arikartech.com')) {
        return 'http://127.0.0.1:8000/api/v1';
      }
    }
  }

  if (process.env.INTERNAL_API_URL) {
    return process.env.INTERNAL_API_URL.replace(/\/+$/, '');
  }

  if (process.env.NODE_ENV === 'development') {
    return (process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1').replace(/\/+$/, '');
  }

  const url = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1';
  return url.replace(/\/+$/, '');
}

// ---------------------------------------------------------------------------
// Public helpers (kept for backward compatibility)
// ---------------------------------------------------------------------------

/** @deprecated Use getApiUrl() from @/lib/url */
export function getApiBaseUrl(): string {
  return apiBase();
}

export function getOutboundUrl(offerId: number | string): string {
  return `${apiBase()}/affiliates/out/${offerId}`;
}

// ---------------------------------------------------------------------------
// Client-side fetch (browser)
// ---------------------------------------------------------------------------

export async function fetchApi<T = any>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const baseUrl = apiBase();
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  const url = `${baseUrl}${cleanEndpoint}`;

  try {
    const res = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...options.headers,
      },
    });

    if (!res.ok) {
      if (res.status === 404) {
        throw new Error('Not found');
      }
      throw new Error(`API error: ${res.status}`);
    }

    return await res.json();
  } catch (err: any) {
    console.error(`Fetch error on ${url}:`, err.message);
    throw err;
  }
}

// ---------------------------------------------------------------------------
// Server-side fetch (Next.js server components / generateStaticParams)
// ---------------------------------------------------------------------------

/**
 * Fetch from the API in Next.js server components or generateStaticParams.
 *
 * Uses Next.js fetch() caching semantics:
 *   - { cache: 'no-store' }      → always fresh (runtime)
 *   - { next: { revalidate: N }} → ISR-style (if server available)
 *   - { cache: 'force-cache' }   → static build cache (default for generateStaticParams)
 *
 * @param endpoint  Path relative to API base, e.g. '/products?per_page=200'
 * @param options   fetch() options; default cache is force-cache for build-time use
 */
export async function fetchApiServer<T = any>(
  endpoint: string,
  options: RequestInit & { next?: { revalidate?: number } } = {},
): Promise<T | null> {
  // During static export builds, the API must be reachable from the build machine.
  // In production builds this is api.arikartech.com; locally it's 127.0.0.1:8000.
  const baseUrl = apiBase();
  const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  const url = `${baseUrl}${cleanEndpoint}`;

  try {
    const res = await fetch(url, {
      cache: 'force-cache',   // build-time default — override per call if needed
      ...options,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(options.headers ?? {}),
      },
    });

    if (!res.ok) {
      if (res.status === 404) return null;
      console.error(`[server fetch] API ${res.status} for ${url}`);
      return null;
    }

    return await res.json();
  } catch (err: any) {
    console.error(`[server fetch] Error fetching ${url}:`, err.message);
    return null;
  }
}
