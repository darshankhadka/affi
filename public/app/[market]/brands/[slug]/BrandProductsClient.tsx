'use client';

import React, { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { AlertCircle, RefreshCw } from 'lucide-react';

interface BrandProductsClientProps {
  market: string;
  brandSlug: string;
  brandName: string;
}

export const BrandProductsClient: React.FC<BrandProductsClientProps> = ({
  market,
  brandSlug,
  brandName,
}) => {
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  const loadProducts = () => {
    setLoading(true);
    setError(false);

    fetchApi(`/products?market=${market}&brand=${brandSlug}&per_page=24`)
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
    loadProducts();
  }, [market, brandSlug]);

  if (loading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-pulse">
        {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
          <div key={i} className="bg-white rounded-2xl p-5 h-72 border border-slate-200" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white rounded-2xl p-8 border border-slate-200 text-center space-y-4 shadow-xs">
        <div className="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center mx-auto text-slate-500">
          <AlertCircle className="w-5 h-5" />
        </div>
        <div className="space-y-1">
          <h3 className="text-sm font-semibold text-slate-900">Brand Feed Updating</h3>
          <p className="text-xs text-slate-500 max-w-md mx-auto">
            Live prices for {brandName} in {market.toUpperCase()} are currently refreshing.
          </p>
        </div>
        <button
          onClick={loadProducts}
          className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition-colors shadow-xs"
        >
          <RefreshCw className="w-3.5 h-3.5" /> Refresh Catalog
        </button>
      </div>
    );
  }

  if (products.length > 0) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        {products.map((product) => (
          <ProductCard key={product.id} product={product} market={market} />
        ))}
      </div>
    );
  }

  return (
    <EmptyState
      title={`No ${brandName} products currently indexed in ${market.toUpperCase()}.`}
      description="New merchant inventory feeds are continuously normalized and verified by our bounded catalog engine."
    />
  );
};
