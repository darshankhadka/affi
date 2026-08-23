'use client';

import React, { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { TrendingUp, AlertCircle, RefreshCw } from 'lucide-react';

interface HomeDealsClientProps {
  market: string;
}

export const HomeDealsClient: React.FC<HomeDealsClientProps> = ({ market }) => {
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  const loadDeals = () => {
    setLoading(true);
    setError(false);

    fetchApi(`/products?market=${market}&per_page=12`)
      .then((res) => {
        setProducts(res.data || []);
        setLoading(false);
      })
      .catch(() => {
        setError(true);
        setProducts([]);
        setLoading(false);
      });
  };

  useEffect(() => {
    loadDeals();
  }, [market]);

  return (
    <section className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
          <TrendingUp className="w-4 h-4 text-emerald-700" />
          Top Technology Deals & Products
        </h2>
      </div>

      {loading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-pulse">
          {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
            <div key={i} className="bg-white rounded-2xl p-5 h-72 border border-slate-200" />
          ))}
        </div>
      ) : error ? (
        <div className="bg-white rounded-2xl p-8 border border-slate-200 text-center space-y-4 shadow-xs">
          <div className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-500">
            <AlertCircle className="w-5 h-5" />
          </div>
          <div className="space-y-1">
            <h3 className="text-sm font-semibold text-slate-900">Live Catalog Service Updating</h3>
            <p className="text-xs text-slate-500 max-w-md mx-auto">
              Our automated price crawler is currently refreshing retailer feeds for {market.toUpperCase()}.
            </p>
          </div>
          <button
            onClick={loadDeals}
            className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-colors shadow-xs"
          >
            <RefreshCw className="w-3.5 h-3.5" /> Retry Connection
          </button>
        </div>
      ) : products.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} market={market} />
          ))}
        </div>
      ) : (
        <EmptyState
          title="No products available in this market yet."
          description="Our automated ingestion engine is currently indexing live merchant feeds and prices. Check back shortly."
        />
      )}
    </section>
  );
};
