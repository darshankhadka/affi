import React from 'react';
import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { CANONICAL_MARKETS } from '@/lib/catalog';
import { fetchApiServer } from '@/lib/api';
import { getSiteUrl, buildProductUrl, productPath } from '@/lib/url';
import { ChevronRight } from 'lucide-react';
import Link from 'next/link';
import { ProductDetailClient } from './ProductDetailClient';

// ---------------------------------------------------------------------------
// Routing
// ---------------------------------------------------------------------------

// dynamicParams = false: any slug/market combination NOT in generateStaticParams()
// returns a clean 404 (not a 500). This is required for output: 'export'.
// In dev mode output:'export' is disabled (NEXT_PHASE check in next.config.mjs),
// so any slug resolves dynamically.
export const dynamicParams = true;

interface ProductPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

// ---------------------------------------------------------------------------
// generateStaticParams
//
// Fetches ALL published product slugs from the API at build time and generates
// a static route for every {market} × {slug} combination.
//
// Architecture notes:
//  - This runs during `next build` only (output: 'export' is in NEXT_PHASE guard)
//  - The API must be reachable from the build machine (locally: 127.0.0.1:8000,
//    in CI/CD: api.arikartech.com)
//  - New products require a rebuild to become accessible in production
//  - In `next dev` (no output:'export'), any slug resolves dynamically via the API
// ---------------------------------------------------------------------------

export async function generateStaticParams(): Promise<{ market: string; slug: string }[]> {
  const params: { market: string; slug: string }[] = [];

  try {
    const result = await fetchApiServer<{
      data: { slug: string }[];
    }>('/products?status=published&per_page=100', {
      cache: 'no-store',
    });

    const slugs = (result?.data || []).map((p) => p.slug).filter(Boolean);
    const targetSlugs = slugs.length > 0 ? slugs : ['catalog'];

    for (const market of CANONICAL_MARKETS) {
      for (const slug of targetSlugs) {
        params.push({ market: market.code, slug });
      }
    }
  } catch (err) {
    for (const market of CANONICAL_MARKETS) {
      params.push({ market: market.code, slug: 'catalog' });
    }
  }

  return params;
}

// ---------------------------------------------------------------------------
// generateMetadata
// ---------------------------------------------------------------------------

export async function generateMetadata({ params }: ProductPageProps): Promise<Metadata> {
  const { market, slug } = await params;
  const siteUrl = getSiteUrl();

  // Try to fetch real product metadata from the API
  const result = await fetchApiServer<{ data: { name: string; short_description?: string } }>(
    `/products/${slug}?market=${market}`,
  );

  const product = result?.data;
  const name = product?.name ?? slug.split('-').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
  const description = product?.short_description
    ?? `Compare prices for ${name} across verified tech retailers in ${market.toUpperCase()}.`;

  const canonicalUrl = buildProductUrl(market, slug);

  return {
    title: `${name} Best Price & Deals | ARIKARTECH`,
    description: description.slice(0, 160),
    alternates: {
      canonical: canonicalUrl,
    },
    openGraph: {
      title: `${name} Best Price & Deals | ARIKARTECH`,
      description: description.slice(0, 200),
      url: canonicalUrl,
      siteName: 'ARIKARTECH',
      type: 'website',
    },
    twitter: {
      card: 'summary_large_image',
      title: `${name} Best Price & Deals | ARIKARTECH`,
      description: description.slice(0, 200),
    },
  };
}

// ---------------------------------------------------------------------------
// Page component
// ---------------------------------------------------------------------------

export default async function ProductPage({ params }: ProductPageProps) {
  const { market, slug } = await params;

  // Validate market (belt-and-suspenders; market layout already guards this)
  const validMarket = CANONICAL_MARKETS.some((m) => m.code === market);
  if (!validMarket) {
    notFound();
  }

  return (
    <div className="space-y-8">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-2 text-xs text-slate-500">
        <Link href={`/${market}`} className="hover:text-emerald-600">Home</Link>
        <ChevronRight className="w-3 h-3 text-slate-400" />
        <Link href={`/${market}`} className="hover:text-emerald-600">Products</Link>
        <ChevronRight className="w-3 h-3 text-slate-400" />
        <span className="text-slate-800 font-medium truncate capitalize">
          {slug.replace(/-/g, ' ')}
        </span>
      </nav>

      {/*
        ProductDetailClient fetches live data client-side via the API.
        The page shell above is server-rendered / statically generated at build time,
        providing fast initial paint and correct metadata for crawlers.
      */}
      <ProductDetailClient market={market} slug={slug} />
    </div>
  );
}
