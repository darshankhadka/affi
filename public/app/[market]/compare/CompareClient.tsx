'use client';

import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { productPath } from '@/lib/url';
import { EmptyState } from '@/components/ui/EmptyState';
import { SlidersHorizontal, ArrowRight, Loader2 } from 'lucide-react';
import Link from 'next/link';

interface CompareClientProps {
  market: string;
}

export const CompareClient: React.FC<CompareClientProps> = ({ market }) => {
  const searchParams = useSearchParams();
  const slugs = searchParams.get('slugs') || '';

  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!slugs.trim()) {
      setProducts([]);
      setLoading(false);
      return;
    }

    let isMounted = true;
    setLoading(true);

    fetchApi(`/compare?slugs=${encodeURIComponent(slugs)}&market=${market}`)
      .then((res) => {
        if (isMounted) {
          setProducts(res.data || []);
          setLoading(false);
        }
      })
      .catch(() => {
        if (isMounted) {
          setProducts([]);
          setLoading(false);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [slugs, market]);

  return (
    <div className="space-y-8">
      <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-2">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <SlidersHorizontal className="w-3.5 h-3.5" />
          <span>Hardware Comparison</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Side-by-Side Product Comparison</h1>
        <p className="text-xs text-slate-500">
          {loading ? (
            <span className="inline-flex items-center gap-1.5">
              <Loader2 className="w-3 h-3 animate-spin text-emerald-600" /> Comparing specifications...
            </span>
          ) : (
            'Compare up to 4 models simultaneously across technical specifications and verified prices.'
          )}
        </p>
      </div>

      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 animate-pulse">
          {[1, 2, 3].map((i) => (
            <div key={i} className="bg-white rounded-2xl p-6 h-64 border border-slate-200" />
          ))}
        </div>
      ) : products.length >= 2 ? (
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
                href={productPath(market, p.slug)}
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
};
