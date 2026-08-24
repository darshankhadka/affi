'use client';

import React, { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { getMarketDef } from '@/lib/catalog';

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
  const marketInfo = getMarketDef(market);

  const loadProducts = () => {
    setLoading(true);

    fetchApi(`/products?market=${market}&brand=${brandSlug}&per_page=24`)
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
      title={`No ${brandName} products currently available for ${marketInfo.name}.`}
      description={`Retailer offers and verified prices for ${brandName} in ${marketInfo.name} (${marketInfo.currency}) are continuously synchronized. Check back shortly.`}
    />
  );
};
