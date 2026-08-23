import React from 'react';
import { Metadata } from 'next';
import { fetchApi } from '@/lib/api';
import { EmptyState } from '@/components/ui/EmptyState';
import { SlidersHorizontal, ArrowRight } from 'lucide-react';
import Link from 'next/link';

interface ComparePageProps {
  params: Promise<{ market: string }>;
  searchParams: Promise<{ slugs?: string }>;
}

export const metadata: Metadata = {
  title: 'Compare Hardware Specs & Retailer Prices | ARIKARTECH',
  description: 'Side-by-side technical hardware specifications and multi-store price comparisons.',
};

export default async function ComparePage({ params, searchParams }: ComparePageProps) {
  const { market } = await params;
  const { slugs } = await searchParams;

  let products: any[] = [];
  if (slugs) {
    try {
      const res = await fetchApi(`/compare?slugs=${encodeURIComponent(slugs)}&market=${market}`);
      products = res.data || [];
    } catch {
      products = [];
    }
  }

  return (
    <div className="space-y-8">
      <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-2">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <SlidersHorizontal className="w-3.5 h-3.5" />
          <span>Hardware Comparison</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Side-by-Side Product Comparison</h1>
        <p className="text-xs text-slate-500">Compare up to 4 models simultaneously across technical specifications and verified prices.</p>
      </div>

      {products.length >= 2 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {products.map((p) => (
            <div key={p.id} className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
              <h3 className="font-bold text-base text-slate-900">{p.name}</h3>
              <p className="text-xs text-slate-500 font-mono">Brand: {p.brand?.name}</p>
              {p.best_price && (
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-100">
                  <span className="text-[10px] text-slate-500 block uppercase">Best Price We Found</span>
                  <span className="text-xl font-black text-emerald-700">
                    {p.best_price.currency?.symbol || '$'}{Number(p.best_price.min_price).toFixed(2)}
                  </span>
                </div>
              )}
              <Link
                href={`/${market}/products/${p.slug}`}
                className="inline-flex items-center gap-1 text-xs text-emerald-700 font-semibold hover:underline"
              >
                View Full Deals <ArrowRight className="w-3 h-3" />
              </Link>
            </div>
          ))}
        </div>
      ) : (
        <EmptyState
          title="No products selected for comparison."
          description="Select 2 to 4 products from the catalog to compare their technical specifications and best retailer prices side by side."
          actionHref={`/${market}`}
          actionText="Browse Hardware Catalog"
        />
      )}
    </div>
  );
}
