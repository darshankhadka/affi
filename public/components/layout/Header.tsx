'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter, usePathname } from 'next/navigation';
import { Search, Globe, SlidersHorizontal, X, Check } from 'lucide-react';
import { CANONICAL_MARKETS, MarketDef } from '@/lib/catalog';
import { setUserMarketPreference } from '@/lib/market-router';

interface HeaderProps {
  currentMarket: string;
  markets?: MarketDef[];
  categories?: { name: string; slug: string }[];
}

const MARKET_FLAGS: Record<string, string> = {
  us: '🇺🇸', ca: '🇨🇦', gb: '🇬🇧', de: '🇩🇪', fr: '🇫🇷', nl: '🇳🇱',
  es: '🇪🇸', it: '🇮🇹', be: '🇧🇪', at: '🇦🇹', ie: '🇮🇪', pt: '🇵🇹',
  fi: '🇫🇮', se: '🇸🇪', dk: '🇩🇰', pl: '🇵🇱', cz: '🇨🇿', bg: '🇧🇬',
  hr: '🇭🇷', cy: '🇨🇾', ee: '🇪🇪', gr: '🇬🇷', hu: '🇭🇺', lv: '🇱🇻',
  lt: '🇱🇹', lu: '🇱🇺', mt: '🇲🇹', ro: '🇷🇴', sk: '🇸🇰', si: '🇸🇮',
  no: '🇳🇴', ch: '🇨🇭', is: '🇮🇸', au: '🇦🇺', nz: '🇳🇿',
};

export const Header: React.FC<HeaderProps> = ({
  currentMarket,
  markets = CANONICAL_MARKETS,
  categories = [
    { name: 'Laptops', slug: 'laptops' },
    { name: 'Gaming Laptops', slug: 'gaming-laptops' },
    { name: 'MacBooks', slug: 'macbooks' },
    { name: 'Smartphones', slug: 'smartphones' },
    { name: 'GPUs', slug: 'gpus-graphics-cards' },
    { name: 'CPUs', slug: 'cpus-processors' },
    { name: 'Monitors', slug: 'gaming-monitors' },
    { name: 'SSDs & Storage', slug: 'ssds-storage' },
    { name: 'RAM', slug: 'ram-memory' },
    { name: 'Tablets', slug: 'tablets-ipads' },
    { name: 'Smartwatches', slug: 'smartwatches' },
    { name: 'Keyboards', slug: 'mechanical-keyboards' },
    { name: 'Mice', slug: 'gaming-mice' },
    { name: 'Headphones', slug: 'headphones-audio' },
    { name: 'Routers', slug: 'routers-mesh-wifi' },
  ],
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [isMarketModalOpen, setIsMarketModalOpen] = useState(false);
  const [marketSearch, setMarketSearch] = useState('');
  const router = useRouter();
  const pathname = usePathname();

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      router.push(`/${currentMarket}/search?q=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  const handleSelectMarket = (targetMarketCode: string) => {
    setUserMarketPreference(targetMarketCode);
    setIsMarketModalOpen(false);

    // If on a market-specific route, replace the market segment
    if (pathname) {
      const segments = pathname.split('/').filter(Boolean);
      if (segments.length > 0 && markets.some((m) => m.code === segments[0])) {
        segments[0] = targetMarketCode;
        router.push(`/${segments.join('/')}`);
        return;
      }
    }

    router.push(`/${targetMarketCode}`);
  };

  const currentMarketDef = markets.find((m) => m.code === currentMarket) || markets[0];
  const currentFlag = MARKET_FLAGS[currentMarket] || '🌐';

  const filteredMarkets = markets.filter(
    (m) =>
      m.name.toLowerCase().includes(marketSearch.toLowerCase()) ||
      m.code.toLowerCase().includes(marketSearch.toLowerCase()) ||
      m.currency.toLowerCase().includes(marketSearch.toLowerCase())
  );

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
            <span className="text-[10px] text-emerald-600 block -mt-1 font-mono font-bold tracking-tight">GLOBAL TECH ENGINE</span>
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
        <div className="flex items-center gap-3">
          <Link
            href={`/${currentMarket}/compare`}
            className="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 hover:text-emerald-600 hover:border-slate-300 transition-colors"
          >
            <SlidersHorizontal className="w-3.5 h-3.5" />
            Compare
          </Link>

          {/* Interactive Market Switcher Button */}
          <button
            type="button"
            onClick={() => setIsMarketModalOpen(true)}
            className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all cursor-pointer"
            title="Switch Global Regional Market (35 Markets)"
          >
            <span className="text-base leading-none">{currentFlag}</span>
            <span className="uppercase font-mono font-bold text-slate-900">{currentMarket}</span>
            <span className="text-slate-400 text-[11px]">({currentMarketDef.currency})</span>
          </button>
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

      {/* 35-Market Selector Modal */}
      {isMarketModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden">
            {/* Modal Header */}
            <div className="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold text-slate-900 flex items-center gap-2">
                  <Globe className="w-5 h-5 text-emerald-600" />
                  Select Regional Market & Currency
                </h3>
                <p className="text-xs text-slate-500 mt-0.5">
                  Choose from 35 supported technology markets across North America, Europe, and Oceania.
                </p>
              </div>
              <button
                type="button"
                onClick={() => setIsMarketModalOpen(false)}
                className="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Filter Search */}
            <div className="p-4 border-b border-slate-100 bg-slate-50">
              <div className="relative">
                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  placeholder="Search by country name, code or currency (e.g. Germany, GBP, SEK, US)..."
                  value={marketSearch}
                  onChange={(e) => setMarketSearch(e.target.value)}
                  className="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                  autoFocus
                />
              </div>
            </div>

            {/* Market Grid */}
            <div className="p-4 sm:p-6 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
              {filteredMarkets.map((m) => {
                const isSelected = m.code === currentMarket;
                const flag = MARKET_FLAGS[m.code] || '🌐';
                return (
                  <button
                    key={m.code}
                    type="button"
                    onClick={() => handleSelectMarket(m.code)}
                    className={`flex items-center justify-between p-3 rounded-xl border text-left transition-all cursor-pointer ${
                      isSelected
                        ? 'border-emerald-500 bg-emerald-50/80 shadow-xs'
                        : 'border-slate-200 hover:border-emerald-300 hover:bg-slate-50'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <span className="text-2xl leading-none">{flag}</span>
                      <div>
                        <div className="font-semibold text-xs text-slate-900">{m.name}</div>
                        <div className="text-[10px] text-slate-500 font-mono">
                          {m.currency} ({m.symbol}) • {m.code.toUpperCase()}
                        </div>
                      </div>
                    </div>
                    {isSelected && <Check className="w-4 h-4 text-emerald-600" />}
                  </button>
                );
              })}
            </div>

            {/* Modal Footer */}
            <div className="p-3 bg-slate-50 border-t border-slate-100 text-center text-[11px] text-slate-500">
              Your selection is saved securely and persists across future sessions.
            </div>
          </div>
        </div>
      )}
    </header>
  );
};
