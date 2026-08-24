'use client';

import React, { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { TrendingUp } from 'lucide-react';
import { getMarketDef } from '@/lib/catalog';

interface HomeDealsClientProps {
  market: string;
}

export const HomeDealsClient: React.FC<HomeDealsClientProps> = ({ market }) => {
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const marketInfo = getMarketDef(market);

  const loadDeals = () => {
    setLoading(true);

    fetchApi(`/products?market=${market}&per_page=12`)
      .then((res) => {
        setProducts(res.data || []);
        setLoading(false);
      })
      .catch(() => {
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
      ) : products.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} market={market} />
          ))}
        </div>
      ) : (
        <EmptyState
          title={`No live offers are available for ${marketInfo.name} yet.`}
          description={`Prices and deals are continuously indexed across authorized retailers in ${marketInfo.name} (${marketInfo.currency}). Check back shortly or explore categories above.`}
        />
      )}
    </section>
  );
};
