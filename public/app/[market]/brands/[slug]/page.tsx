import React from 'react';
import { Metadata } from 'next';
import { CANONICAL_MARKETS, CANONICAL_BRANDS, getBrandDef } from '@/lib/catalog';
import { Tag } from 'lucide-react';
import { BrandProductsClient } from './BrandProductsClient';

export const dynamicParams = true;

interface BrandPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateStaticParams() {
  const params: { market: string; slug: string }[] = [];

  for (const m of CANONICAL_MARKETS) {
    for (const b of CANONICAL_BRANDS) {
      params.push({ market: m.code, slug: b.slug });
    }
  }

  return params;
}

export async function generateMetadata({ params }: BrandPageProps): Promise<Metadata> {
  const { market, slug } = await params;
  const brand = getBrandDef(slug);

  return {
    title: `Best ${brand.name} Deals & Price Comparison (${market.toUpperCase()}) | ARIKARTECH`,
    description: brand.description || `Compare prices and discover verified ${brand.name} hardware from authorized retailers in ${market.toUpperCase()} on ARIKARTECH.`,
    alternates: {
      canonical: `https://arikartech.com/${market}/brands/${slug}`,
    },
  };
}

export default async function BrandPage({ params }: BrandPageProps) {
  const { market, slug } = await params;
  const brand = getBrandDef(slug);

  return (
    <div className="space-y-8">
      {/* Brand Header */}
      <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-3">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Tag className="w-3.5 h-3.5" />
          <span>Brand Discovery</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
          {brand.name} Price Comparison & Deals
        </h1>
        <p className="text-xs text-slate-500 max-w-2xl">
          {brand.description || `Compare verified store offers and hardware specs for ${brand.name} products in the ${market.toUpperCase()} market.`}
        </p>
      </div>

      {/* Products Grid (Client Hydrated via API) */}
      <BrandProductsClient
        market={market}
        brandSlug={slug}
        brandName={brand.name}
      />
    </div>
  );
}
