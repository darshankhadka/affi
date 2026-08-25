'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import {
  Search,
  Laptop,
  Gamepad2,
  Smartphone,
  Cpu,
  Monitor,
  Headphones,
  Tv,
  ArrowRight,
  Sparkles,
  Tag,
  Flame,
  Layers,
  ChevronRight
} from 'lucide-react';
import { getMarketDef, CANONICAL_CATEGORIES } from '@/lib/catalog';

interface HomeDealsClientProps {
  market: string;
}

export const HomeDealsClient: React.FC<HomeDealsClientProps> = ({ market }) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [featuredProducts, setFeaturedProducts] = useState<any[]>([]);
  const [dealsProducts, setDealsProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [loadingMore, setLoadingMore] = useState(false);

  const router = useRouter();
  const marketInfo = getMarketDef(market);

  // Curated category shortcuts
  const topCategoryShortcuts = [
    { name: 'Laptops', slug: 'laptops', icon: <Laptop className="w-4 h-4 text-emerald-600" /> },
    { name: 'Gaming Laptops', slug: 'gaming-laptops', icon: <Gamepad2 className="w-4 h-4 text-purple-600" /> },
    { name: 'Smartphones', slug: 'smartphones', icon: <Smartphone className="w-4 h-4 text-blue-600" /> },
    { name: 'GPUs & Graphics', slug: 'gpus-graphics-cards', icon: <Cpu className="w-4 h-4 text-teal-600" /> },
    { name: 'Gaming Monitors', slug: 'gaming-monitors', icon: <Monitor className="w-4 h-4 text-indigo-600" /> },
    { name: 'Headphones & Audio', slug: 'headphones-audio', icon: <Headphones className="w-4 h-4 text-amber-600" /> },
    { name: '4K & OLED TVs', slug: '4k-oled-tvs', icon: <Tv className="w-4 h-4 text-rose-600" /> },
  ];

  useEffect(() => {
    setLoading(true);

    Promise.all([
      fetchApi(`/products?market=${market}&per_page=8&page=1`),
      fetchApi(`/products?market=${market}&sort=newest&per_page=8`),
    ])
      .then(([featuredRes, dealsRes]) => {
        setFeaturedProducts(featuredRes.data || []);
        setTotalPages(featuredRes.meta?.last_page || 1);
        setDealsProducts(dealsRes.data || []);
        setLoading(false);
      })
      .catch(() => {
        setLoading(false);
      });
  }, [market]);

  const handleHeroSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      router.push(`/${market}/search?q=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  const loadMoreFeatured = () => {
    if (page >= totalPages || loadingMore) return;
    setLoadingMore(true);
    const nextPage = page + 1;

    fetchApi(`/products?market=${market}&per_page=8&page=${nextPage}`)
      .then((res) => {
        setFeaturedProducts((prev) => [...prev, ...(res.data || [])]);
        setPage(nextPage);
        setLoadingMore(false);
      })
      .catch(() => setLoadingMore(false));
  };

  return (
    <div className="space-y-16 pb-12">
      
      {/* 1. Large Minimal Hero */}
      <section className="text-center py-10 sm:py-16 space-y-6 max-w-3xl mx-auto px-4">
        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold shadow-2xs font-mono">
          <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
          <span>Real-Time Technology Price Comparison</span>
        </div>

        <h1 className="text-4xl sm:text-6xl font-black tracking-tight text-slate-900 leading-[1.12]">
          Find the right tech. <br />
          <span className="text-emerald-700">Compare the real prices.</span>
        </h1>

        <p className="text-sm sm:text-base text-slate-600 max-w-xl mx-auto leading-relaxed">
          Search thousands of products across trusted tech retailers in {marketInfo.name}.
        </p>

        {/* Hero Search Box */}
        <form onSubmit={handleHeroSearch} className="max-w-2xl mx-auto pt-2">
          <div className="relative flex items-center bg-white rounded-2xl p-2 border-2 border-slate-200 focus-within:border-emerald-500 shadow-lg shadow-slate-200/50 transition-all">
            <Search className="w-5 h-5 text-slate-400 ml-3 shrink-0" />
            <input
              type="text"
              placeholder="Search laptops, GPUs, phones, monitors, models..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full px-3 py-2 text-sm sm:text-base text-slate-900 placeholder-slate-400 bg-transparent focus:outline-none"
            />
            <button
              type="submit"
              className="px-5 sm:px-7 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs sm:text-sm uppercase tracking-wider transition-colors shrink-0 shadow-sm"
            >
              Search
            </button>
          </div>
        </form>
      </section>

      {/* 2. Shop by Category Shortcuts (Section 10) */}
      <section className="space-y-4 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between">
          <h2 className="text-sm font-bold uppercase tracking-wider text-slate-900 font-mono flex items-center gap-2">
            <Layers className="w-4 h-4 text-emerald-600" />
            Shop by Category
          </h2>
          <Link
            href={`/${market}/search`}
            className="text-xs font-semibold text-emerald-700 hover:text-emerald-800 flex items-center gap-1"
          >
            All 20 Categories <ChevronRight className="w-3.5 h-3.5" />
          </Link>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
          {topCategoryShortcuts.map((cat) => (
            <Link
              key={cat.slug}
              href={`/${market}/categories/${cat.slug}`}
              className="bg-white rounded-xl p-3.5 flex flex-col items-center justify-center text-center border border-slate-200 shadow-2xs hover:border-emerald-500 hover:bg-emerald-50/30 hover:shadow-xs transition-all group"
            >
              <div className="p-2 rounded-lg bg-slate-50 group-hover:bg-white mb-2 transition-colors">
                {cat.icon}
              </div>
              <span className="font-bold text-xs text-slate-800 group-hover:text-emerald-800 transition-colors">
                {cat.name}
              </span>
            </Link>
          ))}

          {/* More Categories Link */}
          <Link
            href={`/${market}/search`}
            className="bg-slate-50 rounded-xl p-3.5 flex flex-col items-center justify-center text-center border border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50/40 transition-all group"
          >
            <div className="p-2 rounded-lg bg-white mb-2">
              <ArrowRight className="w-4 h-4 text-emerald-600" />
            </div>
            <span className="font-bold text-xs text-emerald-700 group-hover:text-emerald-800">
              More →
            </span>
          </Link>
        </div>
      </section>

      {/* 3. Featured Hardware Products (Section 11) */}
      <section className="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div className="flex items-center justify-between border-b border-slate-200 pb-3">
          <div>
            <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
              <Sparkles className="w-5 h-5 text-emerald-600" />
              Featured Technology
            </h2>
            <p className="text-xs text-slate-500 mt-0.5">
              Verified hardware with live price indexes in {marketInfo.name}
            </p>
          </div>
        </div>

        {loading ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 animate-pulse">
            {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
              <div key={i} className="bg-white rounded-2xl p-5 h-80 border border-slate-200" />
            ))}
          </div>
        ) : featuredProducts.length > 0 ? (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
              {featuredProducts.map((product) => (
                <ProductCard key={product.id} product={product} market={market} />
              ))}
            </div>

            {page < totalPages && (
              <div className="text-center pt-6">
                <button
                  type="button"
                  onClick={loadMoreFeatured}
                  disabled={loadingMore}
                  className="px-8 py-3 rounded-xl bg-white border border-slate-300 hover:border-emerald-600 text-slate-800 font-bold text-xs uppercase tracking-wider shadow-2xs hover:shadow-xs transition-all hover:bg-emerald-50/20"
                >
                  {loadingMore ? 'Loading Tech Products...' : 'Load More Products'}
                </button>
              </div>
            )}
          </>
        ) : (
          <EmptyState
            title={`No published products currently active in ${marketInfo.name}`}
            description={`Prices are synchronized continuously across authorized retailers in ${marketInfo.name}. Explore categories above.`}
          />
        )}
      </section>

      {/* 4. Today's Deals Section (Section 12) */}
      <section id="deals" className="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 border-t border-slate-200">
        <div className="flex items-center justify-between border-b border-slate-200 pb-3">
          <div>
            <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
              <Flame className="w-5 h-5 text-rose-600" />
              Today&apos;s Tech Deals
            </h2>
            <p className="text-xs text-slate-500 mt-0.5">
              Lowest verified retailer offers across approved technology categories
            </p>
          </div>
        </div>

        {loading ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 animate-pulse">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="bg-white rounded-2xl p-5 h-80 border border-slate-200" />
            ))}
          </div>
        ) : dealsProducts.length > 0 ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            {dealsProducts.map((product) => (
              <ProductCard key={product.id} product={product} market={market} />
            ))}
          </div>
        ) : null}
      </section>

    </div>
  );
};
