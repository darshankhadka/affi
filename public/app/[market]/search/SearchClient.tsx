'use client';

import React, { useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { Search, Loader2 } from 'lucide-react';

interface SearchClientProps {
  market: string;
}

export const SearchClient: React.FC<SearchClientProps> = ({ market }) => {
  const searchParams = useSearchParams();
  const q = searchParams.get('q') || '';

  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [hasSearched, setHasSearched] = useState(false);

  useEffect(() => {
    if (!q.trim()) {
      setProducts([]);
      setLoading(false);
      setHasSearched(false);
      return;
    }

    let isMounted = true;
    setLoading(true);
    setHasSearched(true);

    fetchApi(`/products?market=${market}&q=${encodeURIComponent(q)}&per_page=24`)
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
  }, [q, market]);

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
          {loading ? (
            <span className="inline-flex items-center gap-1.5">
              <Loader2 className="w-3 h-3 animate-spin text-emerald-600" /> Searching catalog...
            </span>
          ) : hasSearched ? (
            `${products.length} ${products.length === 1 ? 'matching item' : 'matching items'} found in ${market.toUpperCase()} market.`
          ) : (
            `Search thousands of verified technology specs and deals in ${market.toUpperCase()}.`
          )}
        </p>
      </div>

      {loading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-pulse">
          {[1, 2, 3, 4].map((i) => (
            <div key={i} className="bg-white rounded-2xl p-5 h-64 border border-slate-200" />
          ))}
        </div>
      ) : products.length > 0 ? (
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
};
