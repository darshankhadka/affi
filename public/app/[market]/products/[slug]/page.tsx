import React from 'react';
import { Metadata } from 'next';
import { CANONICAL_MARKETS } from '@/lib/catalog';
import { ChevronRight } from 'lucide-react';
import Link from 'next/link';
import { ProductDetailClient } from './ProductDetailClient';

export const dynamicParams = false;

interface ProductPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateStaticParams() {
  const defaultSlugs = ['catalog'];
  const params: { market: string; slug: string }[] = [];

  for (const m of CANONICAL_MARKETS) {
    for (const slug of defaultSlugs) {
      params.push({ market: m.code, slug });
    }
  }

  return params;
}

export async function generateMetadata({ params }: ProductPageProps): Promise<Metadata> {
  const { market, slug } = await params;
  const formattedName = slug
    .split('-')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');

  return {
    title: `${formattedName} Best Price & Deals | ARIKARTECH`,
    description: `Compare prices for ${formattedName} across verified tech retailers in ${market.toUpperCase()}.`,
    alternates: {
      canonical: `https://arikartech.com/${market}/products/${slug}`,
    },
  };
}

export default async function ProductPage({ params }: ProductPageProps) {
  const { market, slug } = await params;

  return (
    <div className="space-y-8">
      {/* Breadcrumb Bar */}
      <nav className="flex items-center gap-2 text-xs text-slate-500">
        <Link href={`/${market}`} className="hover:text-emerald-600">Home</Link>
        <ChevronRight className="w-3 h-3 text-slate-400" />
        <Link href={`/${market}`} className="hover:text-emerald-600">Products</Link>
        <ChevronRight className="w-3 h-3 text-slate-400" />
        <span className="text-slate-800 font-medium truncate capitalize">
          {slug.replace(/-/g, ' ')}
        </span>
      </nav>

      {/* Live Product Details & Retailer Price Engine (Client Hydrated via API) */}
      <ProductDetailClient market={market} slug={slug} />
    </div>
  );
}
