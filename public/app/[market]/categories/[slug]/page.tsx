import React from 'react';
import { Metadata } from 'next';
import { CANONICAL_MARKETS, CANONICAL_CATEGORIES, getCategoryDef } from '@/lib/catalog';
import { Layers } from 'lucide-react';
import { CategoryProductsClient } from './CategoryProductsClient';

export const dynamicParams = true;

interface CategoryPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateStaticParams() {
  const params: { market: string; slug: string }[] = [];

  for (const m of CANONICAL_MARKETS) {
    for (const cat of CANONICAL_CATEGORIES) {
      params.push({ market: m.code, slug: cat.slug });
    }
  }

  return params;
}

export async function generateMetadata({ params }: CategoryPageProps): Promise<Metadata> {
  const { market, slug } = await params;
  const category = getCategoryDef(slug);

  return {
    title: `Best ${category.name} Deals & Price Comparison (${market.toUpperCase()}) | ARIKARTECH`,
    description: category.description || `Compare prices and discover top-rated ${category.name} from authorized retailers in ${market.toUpperCase()} on ARIKARTECH.`,
    alternates: {
      canonical: `https://arikartech.com/${market}/categories/${slug}`,
    },
  };
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { market, slug } = await params;
  const category = getCategoryDef(slug);

  return (
    <div className="space-y-8">
      {/* Category Header */}
      <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-3">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Layers className="w-3.5 h-3.5" />
          <span>Category Discovery</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
          {category.name} Price Comparison
        </h1>
        {category.description && (
          <p className="text-xs text-slate-500 max-w-2xl">{category.description}</p>
        )}
      </div>

      {/* Products Grid (Client Hydrated via API) */}
      <CategoryProductsClient
        market={market}
        categorySlug={slug}
        categoryName={category.name}
      />
    </div>
  );
}
