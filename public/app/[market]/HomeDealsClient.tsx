'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import {
  TrendingUp,
  Sparkles,
  Zap,
  Layers,
  ArrowRight,
  Clock,
  CheckCircle2,
  Tag,
  ChevronRight
} from 'lucide-react';
import { getMarketDef } from '@/lib/catalog';

interface HomeDealsClientProps {
  market: string;
}

export const HomeDealsClient: React.FC<HomeDealsClientProps> = ({ market }) => {
  const [featuredProducts, setFeaturedProducts] = useState<any[]>([]);
  const [latestProducts, setLatestProducts] = useState<any[]>([]);
  const [categories, setCategories] = useState<any[]>([]);
  const [brands, setBrands] = useState<any[]>([]);
  const [activeTab, setActiveTab] = useState<'featured' | 'latest'>('featured');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);

  const marketInfo = getMarketDef(market);

  // Load initial data
  useEffect(() => {
    setLoading(true);

    Promise.all([
      fetchApi(`/products?market=${market}&per_page=12&page=1`),
      fetchApi(`/products?market=${market}&sort=newest&per_page=8`),
      fetchApi(`/categories`),
      fetchApi(`/brands?limit=12`),
    ])
      .then(([productsRes, latestRes, catsRes, brandsRes]) => {
        setFeaturedProducts(productsRes.data || []);
        setTotalPages(productsRes.meta?.last_page || 1);
        setLatestProducts(latestRes.data || []);
        setCategories((catsRes.data || []).slice(0, 12));
        setBrands((brandsRes.data || []).slice(0, 12));
        setLoading(false);
      })
      .catch(() => {
        setLoading(false);
      });
  }, [market]);

  const loadMore = () => {
    if (page >= totalPages || loadingMore) return;
    setLoadingMore(true);
    const nextPage = page + 1;

    fetchApi(`/products?market=${market}&per_page=12&page=${nextPage}`)
      .then((res) => {
        setFeaturedProducts((prev) => [...prev, ...(res.data || [])]);
        setPage(nextPage);
        setLoadingMore(false);
      })
      .catch(() => setLoadingMore(false));
  };

  return (
    <div className="space-y-16">
      {/* Featured Discovery Tabs */}
      <section className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setActiveTab('featured')}
              className={`flex items-center gap-2 pb-2 text-base sm:text-lg font-bold border-b-2 transition-all ${
                activeTab === 'featured'
                  ? 'border-emerald-600 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              }`}
            >
              <Sparkles className="w-4 h-4 text-emerald-600" />
              Featured Verified Deals
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('latest')}
              className={`flex items-center gap-2 pb-2 text-base sm:text-lg font-bold border-b-2 transition-all ${
                activeTab === 'latest'
                  ? 'border-emerald-600 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              }`}
            >
              <Clock className="w-4 h-4 text-teal-600" />
              Latest Ingested Hardware
            </button>
          </div>

          <span className="text-xs font-mono text-slate-500">
            Market: <strong className="text-emerald-700 uppercase">{market}</strong> ({marketInfo.name})
          </span>
        </div>

        {loading ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-pulse">
            {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
              <div key={i} className="bg-white rounded-2xl p-5 h-72 border border-slate-200" />
            ))}
          </div>
        ) : (activeTab === 'featured' ? featuredProducts : latestProducts).length > 0 ? (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {(activeTab === 'featured' ? featuredProducts : latestProducts).map((product) => (
                <ProductCard key={product.id} product={product} market={market} />
              ))}
            </div>

            {/* Load More Button for Featured Products */}
            {activeTab === 'featured' && page < totalPages && (
              <div className="text-center pt-8">
                <button
                  type="button"
                  onClick={loadMore}
                  disabled={loadingMore}
                  className="px-8 py-3 rounded-xl bg-white border border-slate-300 hover:border-emerald-600 text-slate-800 font-bold text-xs uppercase tracking-wider shadow-sm hover:shadow-md transition-all hover:bg-emerald-50/30"
                >
                  {loadingMore ? 'Loading Verified Products...' : 'Load More Products'}
                </button>
              </div>
            )}
          </>
        ) : (
          <EmptyState
            title={`No live offers are available for ${marketInfo.name} yet.`}
            description={`Prices and deals are continuously indexed across authorized retailers in ${marketInfo.name} (${marketInfo.currency}). Check back shortly or explore categories above.`}
          />
        )}
      </section>

      {/* Real Indexed Categories Grid */}
      {categories.length > 0 && (
        <section className="space-y-6 pt-4 border-t border-slate-200">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                <Layers className="w-5 h-5 text-emerald-600" />
                Browse By Hardware Category
              </h2>
              <p className="text-xs text-slate-500 mt-0.5">Explore catalog taxonomy with verified retailer coverage</p>
            </div>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            {categories.map((cat) => (
              <Link
                key={cat.slug}
                href={`/${market}/categories/${cat.slug}`}
                className="bg-white rounded-xl p-4 text-center border border-slate-200 shadow-xs hover:border-emerald-500 hover:bg-emerald-50/40 transition-all group"
              >
                <h3 className="font-bold text-xs text-slate-800 group-hover:text-emerald-700 transition-colors">
                  {cat.name}
                </h3>
                <span className="text-[10px] text-slate-400 font-mono mt-1 block">
                  {cat.products_count ? `${cat.products_count} Products` : 'View Models'}
                </span>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Real Indexed Brands Grid */}
      {brands.length > 0 && (
        <section className="space-y-6 pt-4 border-t border-slate-200">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
                <Tag className="w-5 h-5 text-emerald-600" />
                Featured Tech Brands & Manufacturers
              </h2>
              <p className="text-xs text-slate-500 mt-0.5">Verified hardware brands compared across global retailers</p>
            </div>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
            {brands.map((b) => (
              <Link
                key={b.slug}
                href={`/${market}/brands/${b.slug}`}
                className="bg-white rounded-xl p-3 text-center border border-slate-200 shadow-xs hover:border-emerald-400 hover:bg-emerald-50/30 transition-all font-semibold text-xs text-slate-800"
              >
                {b.name}
              </Link>
            ))}
          </div>
        </section>
      )}
    </div>
  );
};
