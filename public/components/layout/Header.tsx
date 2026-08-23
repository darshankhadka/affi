'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Search, Globe, SlidersHorizontal } from 'lucide-react';

interface HeaderProps {
  currentMarket: string;
  markets?: { code: string; name: string }[];
  categories?: { name: string; slug: string }[];
}

export const Header: React.FC<HeaderProps> = ({
  currentMarket,
  markets = [
    { code: 'us', name: 'United States' },
    { code: 'uk', name: 'United Kingdom' },
    { code: 'de', name: 'Germany' },
    { code: 'fr', name: 'France' },
    { code: 'es', name: 'Spain' },
    { code: 'it', name: 'Italy' },
    { code: 'nl', name: 'Netherlands' },
    { code: 'au', name: 'Australia' },
    { code: 'nz', name: 'New Zealand' },
  ],
  categories = [
    { name: 'Laptops', slug: 'laptops' },
    { name: 'Smartphones', slug: 'smartphones' },
    { name: 'GPUs', slug: 'gpus' },
    { name: 'CPUs', slug: 'cpus' },
    { name: 'Monitors', slug: 'monitors' },
    { name: 'SSDs & Storage', slug: 'ssds' },
    { name: 'RAM', slug: 'ram' },
    { name: 'Tablets', slug: 'tablets' },
    { name: 'Smartwatches', slug: 'smartwatches' },
    { name: 'Keyboards', slug: 'keyboards' },
    { name: 'Mice', slug: 'mice' },
    { name: 'Headphones', slug: 'headphones' },
    { name: 'Routers', slug: 'routers' },
  ],
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const router = useRouter();

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      router.push(`/${currentMarket}/search?q=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  return (
    <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200">
      {/* Top bar */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-6">
        {/* Brand Logo */}
        <Link href={`/${currentMarket}`} className="flex items-center gap-3 shrink-0">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white shadow-md shadow-emerald-500/20">
            A
          </div>
          <div>
            <span className="font-extrabold text-lg tracking-wider text-slate-900 uppercase">ARIKARTECH</span>
            <span className="text-[10px] text-emerald-600 block -mt-1 font-mono font-bold tracking-tight">TECH PRICE ENGINE</span>
          </div>
        </Link>

        {/* Global Search Bar */}
        <form onSubmit={handleSearch} className="flex-1 max-w-xl hidden md:block">
          <div className="relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search laptops, GPUs, processors, monitors, models, MPNs, EAN..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-100 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 transition-all"
            />
          </div>
        </form>

        {/* Actions & Market Selector */}
        <div className="flex items-center gap-4">
          <Link
            href={`/${currentMarket}/compare`}
            className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 hover:text-emerald-600 hover:border-slate-300 transition-colors"
          >
            <SlidersHorizontal className="w-3.5 h-3.5" />
            Compare
          </Link>

          {/* Market Dropdown */}
          <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-medium text-slate-700">
            <Globe className="w-3.5 h-3.5 text-emerald-600" />
            <select
              value={currentMarket}
              onChange={(e) => router.push(`/${e.target.value}`)}
              className="bg-transparent text-slate-800 font-semibold focus:outline-none cursor-pointer"
            >
              {markets.map((m) => (
                <option key={m.code} value={m.code} className="bg-white text-slate-900">
                  {m.code.toUpperCase()}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Category Navigation Bar */}
      <div className="border-t border-slate-100 bg-slate-50/80">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <nav className="flex items-center gap-6 overflow-x-auto py-2.5 scrollbar-none text-xs font-medium text-slate-600">
            {categories.map((cat) => (
              <Link
                key={cat.slug}
                href={`/${currentMarket}/categories/${cat.slug}`}
                className="hover:text-emerald-600 whitespace-nowrap transition-colors"
              >
                {cat.name}
              </Link>
            ))}
          </nav>
        </div>
      </div>
    </header>
  );
};
