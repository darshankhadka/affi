import React from 'react';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { StructuredData } from '@/components/seo/StructuredData';
import { Sparkles, TrendingUp, Layers } from 'lucide-react';
import Link from 'next/link';

interface MarketHomePageProps {
  params: Promise<{ market: string }>;
}

export default async function MarketHomePage({ params }: MarketHomePageProps) {
  const { market } = await params;

  let products: any[] = [];
  let categories: any[] = [];

  try {
    const [productsRes, categoriesRes] = await Promise.all([
      fetchApi(`/products?market=${market}&per_page=12`).catch(() => null),
      fetchApi('/categories').catch(() => null),
    ]);

    if (productsRes?.data) products = productsRes.data;
    if (categoriesRes?.data) categories = categoriesRes.data;
  } catch (err) {
    console.error('Failed to load home data', err);
  }

  const websiteSchema = {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'ARIKARTECH',
    url: `https://arikartech.com/${market}`,
    potentialAction: {
      '@type': 'SearchAction',
      target: `https://arikartech.com/${market}/search?q={search_term_string}`,
      'query-input': 'required name=search_term_string',
    },
  };

  return (
    <div className="space-y-16">
      <StructuredData data={websiteSchema} />

      {/* Hero Section */}
      <section className="relative pt-8 pb-8 text-center space-y-5">
        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-xs">
          <Sparkles className="w-3.5 h-3.5" />
          <span>Real-Time Technology Price Engine</span>
        </div>

        <h1 className="text-4xl sm:text-6xl font-black tracking-tight text-slate-900 max-w-4xl mx-auto leading-[1.15]">
          Discover Hardware. <br />
          <span className="text-emerald-700">
            Compare Real Retailer Prices.
          </span>
        </h1>

        <p className="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
          Index verified tech retailers, discover specifications, track price drops, and find authorized deals across top stores.
        </p>
      </section>

      {/* Category Pills Grid */}
      {categories.length > 0 && (
        <section className="space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold text-slate-900 flex items-center gap-2">
              <Layers className="w-4 h-4 text-emerald-700" />
              Explore Hardware Categories
            </h2>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {categories.map((cat) => (
              <Link
                key={cat.slug}
                href={`/${market}/categories/${cat.slug}`}
                className="bg-white rounded-xl p-4 text-center border border-slate-200 shadow-xs hover:border-emerald-300 hover:bg-emerald-50/50 hover:shadow-sm transition-all duration-200"
              >
                <h3 className="font-semibold text-xs text-slate-800">{cat.name}</h3>
                <p className="text-[10px] text-slate-500 mt-1">Compare Models</p>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Trending / Featured Hardware Catalog */}
      <section className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
            <TrendingUp className="w-4 h-4 text-emerald-700" />
            Top Technology Deals & Products
          </h2>
        </div>

        {products.length > 0 ? (
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
    </div>
  );
}
