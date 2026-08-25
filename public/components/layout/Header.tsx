'use client';

import React, { useState, useRef, useEffect } from 'react';
import Link from 'next/link';
import { useRouter, usePathname } from 'next/navigation';
import { Search, ChevronDown, SlidersHorizontal, Check } from 'lucide-react';
import {
  CANONICAL_MARKETS,
  CANONICAL_MARKET_GROUPS,
  MARKET_FLAGS,
  MarketDef,
  getMarketDef,
} from '@/lib/catalog';
import { setUserMarketPreference } from '@/lib/market-router';

interface HeaderProps {
  currentMarket: string;
  markets?: MarketDef[];
  categories?: { name: string; slug: string }[];
}

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
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [marketSearch, setMarketSearch] = useState('');
  const dropdownRef = useRef<HTMLDivElement>(null);
  const router = useRouter();
  const pathname = usePathname();

  // Close dropdown on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsDropdownOpen(false);
      }
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsDropdownOpen(false);
      }
    };

    if (isDropdownOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('keydown', handleKeyDown);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isDropdownOpen]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      router.push(`/${currentMarket}/search?q=${encodeURIComponent(searchQuery.trim())}`);
    }
  };

  const handleSelectMarket = (targetMarketCode: string) => {
    setUserMarketPreference(targetMarketCode);
    setIsDropdownOpen(false);
    setMarketSearch('');

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

  const currentMarketDef = getMarketDef(currentMarket);
  const currentFlag = MARKET_FLAGS[currentMarketDef.code] || '🌐';

  // Filter market groups based on search
  const filteredGroups = CANONICAL_MARKET_GROUPS.map((group) => ({
    name: group.name,
    markets: group.markets.filter(
      (m) =>
        m.name.toLowerCase().includes(marketSearch.toLowerCase()) ||
        m.code.toLowerCase().includes(marketSearch.toLowerCase()) ||
        m.currency.toLowerCase().includes(marketSearch.toLowerCase())
    ),
  })).filter((group) => group.markets.length > 0);

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

          {/* Compact Non-Blocking Country / Market Selector Popover */}
          <div className="relative" ref={dropdownRef}>
            <button
              type="button"
              onClick={() => setIsDropdownOpen(!isDropdownOpen)}
              className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all cursor-pointer shadow-2xs"
              aria-expanded={isDropdownOpen}
              aria-label={`Select Regional Market (Currently ${currentMarketDef.name})`}
            >
              <span className="text-base leading-none">{currentFlag}</span>
              <span className="font-semibold text-slate-900 hidden sm:inline">{currentMarketDef.name}</span>
              <span className="font-semibold text-slate-900 sm:hidden uppercase font-mono">{currentMarketDef.code}</span>
              <span className="text-slate-400 text-[11px] font-mono">· {currentMarketDef.currency} {currentMarketDef.symbol}</span>
              <ChevronDown className={`w-3.5 h-3.5 text-slate-400 transition-transform duration-150 ${isDropdownOpen ? 'rotate-180 text-emerald-600' : ''}`} />
            </button>

            {/* Compact Popover Dropdown (No full-screen backdrop) */}
            {isDropdownOpen && (
              <div className="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                {/* Search Header */}
                <div className="p-3 border-b border-slate-100 bg-slate-50">
                  <div className="relative">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                      type="text"
                      placeholder="Search 35 markets (e.g. Germany, USD, kr, AU)..."
                      value={marketSearch}
                      onChange={(e) => setMarketSearch(e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      autoFocus
                    />
                  </div>
                </div>

                {/* Grouped Markets List */}
                <div className="max-h-80 overflow-y-auto p-2 space-y-3 scrollbar-thin">
                  {filteredGroups.length === 0 ? (
                    <div className="p-4 text-center text-xs text-slate-500">
                      No markets matching &quot;{marketSearch}&quot;
                    </div>
                  ) : (
                    filteredGroups.map((group) => (
                      <div key={group.name} className="space-y-1">
                        <div className="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">
                          {group.name}
                        </div>
                        <div className="grid grid-cols-1 gap-1">
                          {group.markets.map((m) => {
                            const isSelected = m.code === currentMarketDef.code;
                            const flag = MARKET_FLAGS[m.code] || '🌐';
                            return (
                              <button
                                key={m.code}
                                type="button"
                                onClick={() => handleSelectMarket(m.code)}
                                className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-left text-xs transition-colors cursor-pointer ${
                                  isSelected
                                    ? 'bg-emerald-50 text-emerald-900 font-semibold border border-emerald-200'
                                    : 'text-slate-700 hover:bg-slate-50 hover:text-slate-900 border border-transparent'
                                }`}
                              >
                                <div className="flex items-center gap-2.5">
                                  <span className="text-lg leading-none">{flag}</span>
                                  <div>
                                    <span className="font-medium">{m.name}</span>
                                    <span className="text-[10px] text-slate-400 ml-1.5 font-mono">
                                      {m.currency} ({m.symbol})
                                    </span>
                                  </div>
                                </div>
                                {isSelected && <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />}
                              </button>
                            );
                          })}
                        </div>
                      </div>
                    ))
                  )}
                </div>

                {/* Popover Footer Note */}
                <div className="p-2.5 bg-slate-50 border-t border-slate-100 text-center text-[10px] text-slate-400">
                  Your selected market persists across visits.
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Mobile Search Row (< 768px) */}
      <div className="md:hidden px-4 pb-2.5">
        <form onSubmit={handleSearch}>
          <div className="relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search laptops, GPUs, processors, models..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-100 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500"
            />
          </div>
        </form>
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
