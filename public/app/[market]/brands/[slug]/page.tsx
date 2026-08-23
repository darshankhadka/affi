import React from 'react';
import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { Tag } from 'lucide-react';

interface BrandPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateMetadata({ params }: BrandPageProps): Promise<Metadata> {
  const { market, slug } = await params;

  try {
    const res = await fetchApi(`/brands/${slug}`);
    const brand = res.data;
    if (!brand) return {};

    return {
      title: `Best ${brand.name} Deals & Price Comparison (${market.toUpperCase()}) | ARIKARTECH`,
      description: `Compare prices and discover verified ${brand.name} hardware from authorized retailers in ${market.toUpperCase()} on ARIKARTECH.`,
    };
  } catch {
    return { title: 'Brand | ARIKARTECH' };
  }
}

export default async function BrandPage({ params }: BrandPageProps) {
  const { market, slug } = await params;

  let brand: any = null;
  let products: any[] = [];

  try {
    const [brandRes, prodRes] = await Promise.all([
      fetchApi(`/brands/${slug}`).catch(() => null),
      fetchApi(`/products?market=${market}&brand=${slug}&per_page=24`).catch(() => null),
    ]);

    brand = brandRes?.data;
    products = prodRes?.data || [];
  } catch {
    notFound();
  }

  if (!brand) notFound();

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
          Compare verified store offers and hardware specs for {brand.name} products in the {market.toUpperCase()} market.
        </p>
      </div>

      {/* Products Grid */}
      {products.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} market={market} />
          ))}
        </div>
      ) : (
        <EmptyState
          title={`No ${brand.name} products currently indexed in ${market.toUpperCase()}.`}
          description="New merchant inventory feeds are continuously normalized and verified by our bounded catalog engine."
        />
      )}
    </div>
  );
}
