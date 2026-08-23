import React from 'react';
import { Metadata } from 'next';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { Search } from 'lucide-react';

interface SearchPageProps {
  params: Promise<{ market: string }>;
  searchParams: Promise<{ q?: string }>;
}

export async function generateMetadata({ searchParams }: SearchPageProps): Promise<Metadata> {
  const { q } = await searchParams;
  return {
    title: q ? `Search results for "${q}" | ARIKARTECH` : 'Search Technology Catalog | ARIKARTECH',
    robots: { index: false, follow: true }, // Prevent search result index bloat
  };
}

export default async function SearchPage({ params, searchParams }: SearchPageProps) {
  const { market } = await params;
  const { q } = await searchParams;

  let products: any[] = [];
  if (q) {
    try {
      const res = await fetchApi(`/products?market=${market}&q=${encodeURIComponent(q)}&per_page=24`);
      products = res.data || [];
    } catch {
      products = [];
    }
  }

  return (
    <div className="space-y-8">
      <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-2">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Search className="w-3.5 h-3.5" />
          <span>Catalog Search</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
          {q ? `Search Results for "${q}"` : 'Hardware Search'}
        </h1>
        <p className="text-xs text-slate-500">
          {products.length} {products.length === 1 ? 'matching item' : 'matching items'} found in {market.toUpperCase()} market.
        </p>
      </div>

      {products.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} market={market} />
          ))}
        </div>
      ) : (
        <EmptyState
          title={q ? `No products matching "${q}" found.` : 'Enter a query to search the catalog.'}
          description="Try searching for another hardware brand, model number, or component name."
          actionHref={`/${market}`}
          actionText="Back to Catalog"
        />
      )}
    </div>
  );
}
