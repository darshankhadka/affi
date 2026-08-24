import { MetadataRoute } from 'next';
import {
  CANONICAL_MARKETS,
  CANONICAL_CATEGORIES,
  CANONICAL_BRANDS,
} from '@/lib/catalog';
import { getSiteUrl } from '@/lib/url';

export const dynamic = 'force-static';

/**
 * ARIKARTECH Sitemap
 *
 * Static sitemap generated during `next build`.
 *
 * Includes:
 *   - Root homepage
 *   - Market homepages
 *   - Static pages
 *   - Category pages
 *   - Brand pages
 *   - Published product pages fetched from the API at build time
 *
 * IMPORTANT:
 * This project uses `output: 'export'`, so API requests here MUST
 * be statically cacheable. Do NOT use `cache: 'no-store'`.
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const siteUrl = getSiteUrl();
  const staticPages = [
    'about',
    'privacy',
    'terms',
    'disclosure',
    'contact',
  ];

  const now = new Date();

  const entries: MetadataRoute.Sitemap = [
    {
      url: siteUrl,
      lastModified: now,
      changeFrequency: 'daily',
      priority: 1.0,
    },
  ];

  // ─────────────────────────────────────────────────────────────
  // Market homepages + static pages
  // ─────────────────────────────────────────────────────────────

  for (const market of CANONICAL_MARKETS) {
    entries.push({
      url: `${siteUrl}/${market.code}`,
      lastModified: now,
      changeFrequency: 'daily',
      priority: 0.9,
    });

    for (const page of staticPages) {
      entries.push({
        url: `${siteUrl}/${market.code}/${page}`,
        lastModified: now,
        changeFrequency: 'monthly',
        priority: 0.5,
      });
    }

    // Category pages
    for (const category of CANONICAL_CATEGORIES) {
      entries.push({
        url: `${siteUrl}/${market.code}/categories/${category.slug}`,
        lastModified: now,
        changeFrequency: 'weekly',
        priority: 0.8,
      });
    }

    // Brand pages
    for (const brand of CANONICAL_BRANDS) {
      entries.push({
        url: `${siteUrl}/${market.code}/brands/${brand.slug}`,
        lastModified: now,
        changeFrequency: 'weekly',
        priority: 0.7,
      });
    }
  }

  // ─────────────────────────────────────────────────────────────
  // Product pages
  //
  // IMPORTANT:
  // Use a normal cached fetch because this sitemap is generated
  // during `next build` and the application uses `output: 'export'`.
  // ─────────────────────────────────────────────────────────────

  try {
    const apiUrl =
      process.env.NEXT_PUBLIC_API_URL ||
      'https://api.arikartech.com/api/v1';

    const apiBase = apiUrl.replace(/\/+$/, '');

    const products: {
      slug: string;
      updated_at?: string;
    }[] = [];

    const firstPageResponse = await fetch(
      `${apiBase}/products?status=published&per_page=500`,
      {
        headers: {
          Accept: 'application/json',
        },
        next: {
          revalidate: 3600,
        },
      },
    );

    if (!firstPageResponse.ok) {
      console.error(
        `[sitemap] API returned HTTP ${firstPageResponse.status}`,
      );
    } else {
      const firstPage = await firstPageResponse.json();

      products.push(...(firstPage?.data ?? []));

      const lastPage = Number(firstPage?.meta?.last_page ?? 1);
      const perPage = Number(firstPage?.meta?.per_page ?? 500);

      // Fetch additional API pages when necessary.
      for (let page = 2; page <= lastPage; page++) {
        try {
          const pageResponse = await fetch(
            `${apiBase}/products?status=published&per_page=${perPage}&page=${page}`,
            {
              headers: {
                Accept: 'application/json',
              },
              next: {
                revalidate: 3600,
              },
            },
          );

          if (!pageResponse.ok) {
            console.error(
              `[sitemap] API page ${page} returned HTTP ${pageResponse.status}`,
            );
            continue;
          }

          const pageResult = await pageResponse.json();

          products.push(...(pageResult?.data ?? []));
        } catch (pageError) {
          console.error(
            `[sitemap] Failed to fetch API page ${page}:`,
            pageError,
          );
        }
      }
    }

    // ───────────────────────────────────────────────────────────
    // Emit one URL per market × product
    // ───────────────────────────────────────────────────────────

    for (const product of products) {
      if (!product?.slug) continue;

      const parsedLastModified = product.updated_at
        ? new Date(product.updated_at)
        : now;

      const lastModified = Number.isNaN(parsedLastModified.getTime())
        ? now
        : parsedLastModified;

      for (const market of CANONICAL_MARKETS) {
        entries.push({
          url: `${siteUrl}/${market.code}/products/${product.slug}`,
          lastModified,
          changeFrequency: 'daily',
          priority: 0.85,
        });
      }
    }

    console.log(
      `[sitemap] Generated ${products.length} product URLs × ${CANONICAL_MARKETS.length} markets`,
    );
  } catch (error) {
    // The static sitemap must never make the entire build fail.
    console.error('[sitemap] Failed to fetch products from API:', error);
  }

  return entries;
}