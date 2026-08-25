'use client';

import React, { useState, useRef, useEffect } from 'react';
import Link from 'next/link';
import { useRouter, usePathname } from 'next/navigation';
import {
  Search,
  ChevronDown,
  SlidersHorizontal,
  Check,
  Menu,
  X,
  Laptop,
  Cpu,
  Smartphone,
  Tv,
  Gamepad2,
  Sparkles,
  ArrowRight
} from 'lucide-react';
import { BrandLogo } from '@/components/ui/BrandLogo';
import {
  CANONICAL_MARKETS,
  CANONICAL_MARKET_GROUPS,
  CATEGORY_GROUPS,
  MARKET_FLAGS,
  MarketDef,
  getMarketDef,
} from '@/lib/catalog';
import { setUserMarketPreference } from '@/lib/market-router';

interface HeaderProps {
  currentMarket: string;
  markets?: MarketDef[];
  categories?: any[];
}

export const Header: React.FC<HeaderProps> = ({
  currentMarket,
  markets = CANONICAL_MARKETS,
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [isShopMenuOpen, setIsShopMenuOpen] = useState(false);
  const [isMarketOpen, setIsMarketOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [marketSearch, setMarketSearch] = useState('');

  const shopMenuRef = useRef<HTMLDivElement>(null);
  const marketRef = useRef<HTMLDivElement>(null);
  const router = useRouter();
  const pathname = usePathname();

  // Close menus on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (shopMenuRef.current && !shopMenuRef.current.contains(event.target as Node)) {
        setIsShopMenuOpen(false);
      }
      if (marketRef.current && !marketRef.current.contains(event.target as Node)) {
        setIsMarketOpen(false);
      }
    };

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsShopMenuOpen(false);
        setIsMarketOpen(false);
        setIsMobileMenuOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, []);

  // Close menus on route change
  useEffect(() => {
    setIsShopMenuOpen(false);
    setIsMobileMenuOpen(false);
    setIsMarketOpen(false);
  }, [pathname]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      router.push(`/${currentMarket}/search?q=${encodeURIComponent(searchQuery.trim())}`);
      setIsMobileMenuOpen(false);
    }
  };

  const handleSelectMarket = (targetMarketCode: string) => {
    setUserMarketPreference(targetMarketCode);
    setIsMarketOpen(false);
    setMarketSearch('');

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

  // Group icons helper
  const getGroupIcon = (groupName: string) => {
    switch (groupName) {
      case 'Computers':
        return <Laptop className="w-4 h-4 text-emerald-600" />;
      case 'Components':
        return <Cpu className="w-4 h-4 text-teal-600" />;
      case 'Mobile':
        return <Smartphone className="w-4 h-4 text-blue-600" />;
      case 'Displays & Entertainment':
        return <Tv className="w-4 h-4 text-indigo-600" />;
      case 'Gaming & Peripherals':
        return <Gamepad2 className="w-4 h-4 text-purple-600" />;
      default:
        return <Sparkles className="w-4 h-4 text-emerald-600" />;
    }
  };

  // Filtered market list
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
    <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-2xs">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4 sm:gap-6">
        
        {/* Brand Logo */}
        <Link
          href={`/${currentMarket}`}
          className="flex items-center shrink-0 hover:opacity-95 transition-opacity"
          aria-label="ARIKARTECH Home"
        >
          <BrandLogo className="h-9 w-auto sm:h-10" width={250} height={100} priority />
        </Link>

        {/* Desktop Main Navigation */}
        <nav className="hidden lg:flex items-center gap-1 font-semibold text-xs text-slate-700">
          <Link
            href={`/${currentMarket}`}
            className="px-3 py-2 rounded-xl hover:bg-slate-100 hover:text-slate-900 transition-colors"
          >
            Home
          </Link>

          {/* Shop Mega Menu Dropdown */}
          <div className="relative" ref={shopMenuRef}>
            <button
              type="button"
              onClick={() => setIsShopMenuOpen(!isShopMenuOpen)}
              className={`flex items-center gap-1.5 px-3 py-2 rounded-xl transition-colors cursor-pointer ${
                isShopMenuOpen
                  ? 'bg-emerald-50 text-emerald-800 font-bold'
                  : 'hover:bg-slate-100 hover:text-slate-900'
              }`}
              aria-expanded={isShopMenuOpen}
            >
              <span>Shop</span>
              <ChevronDown
                className={`w-3.5 h-3.5 transition-transform duration-200 ${
                  isShopMenuOpen ? 'rotate-180 text-emerald-600' : 'text-slate-400'
                }`}
              />
            </button>

            {/* Shop Mega Menu Popover */}
            {isShopMenuOpen && (
              <div className="absolute left-0 top-full mt-2 w-[720px] bg-white rounded-2xl shadow-2xl border border-slate-200 p-6 z-50 animate-in fade-in zoom-in-95 duration-150">
                <div className="grid grid-cols-3 gap-6">
                  {CATEGORY_GROUPS.map((group) => (
                    <div key={group.name} className="space-y-2">
                      <div className="flex items-center gap-1.5 pb-1 border-b border-slate-100">
                        {getGroupIcon(group.name)}
                        <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-900 font-mono">
                          {group.name}
                        </h4>
                      </div>
                      <ul className="space-y-1 text-xs">
                        {group.categories.map((cat) => (
                          <li key={cat.slug}>
                            <Link
                              href={`/${currentMarket}/categories/${cat.slug}`}
                              className="block py-1 px-2 rounded-lg text-slate-600 hover:text-emerald-700 hover:bg-emerald-50/60 font-medium transition-colors"
                            >
                              {cat.name}
                            </Link>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ))}
                </div>

                <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs bg-slate-50 -mx-6 -mb-6 p-4 rounded-b-2xl">
                  <span className="text-slate-500 font-mono">20 Canonical Hardware Categories</span>
                  <Link
                    href={`/${currentMarket}/search`}
                    className="font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1"
                  >
                    Browse All Catalog <ArrowRight className="w-3.5 h-3.5" />
                  </Link>
                </div>
              </div>
            )}
          </div>

          <Link
            href={`/${currentMarket}#deals`}
            className="px-3 py-2 rounded-xl hover:bg-slate-100 hover:text-slate-900 transition-colors"
          >
            Deals
          </Link>

          <Link
            href={`/${currentMarket}/compare`}
            className="px-3 py-2 rounded-xl hover:bg-slate-100 hover:text-slate-900 transition-colors"
          >
            Compare
          </Link>
        </nav>

        {/* Global Search Bar */}
        <form onSubmit={handleSearch} className="flex-1 max-w-md hidden md:block">
          <div className="relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search laptops, GPUs, phones, monitors..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-100 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 transition-all"
            />
          </div>
        </form>

        {/* Header Right Actions */}
        <div className="flex items-center gap-2 sm:gap-3">
          <Link
            href={`/${currentMarket}/compare`}
            className="hidden sm:flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 hover:text-emerald-700 hover:border-slate-300 transition-colors"
            title="Side by side product comparison"
          >
            <SlidersHorizontal className="w-3.5 h-3.5" />
            <span className="hidden xl:inline">Compare</span>
          </Link>

          {/* Market / Currency Selector Popover */}
          <div className="relative" ref={marketRef}>
            <button
              type="button"
              onClick={() => setIsMarketOpen(!isMarketOpen)}
              className="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800 hover:border-emerald-500 hover:bg-emerald-50/50 transition-all cursor-pointer shadow-2xs"
              aria-expanded={isMarketOpen}
              aria-label={`Current Market: ${currentMarketDef.name}`}
            >
              <span className="text-base leading-none">{currentFlag}</span>
              <span className="font-semibold text-slate-900 hidden sm:inline">{currentMarketDef.name}</span>
              <span className="font-semibold text-slate-900 sm:hidden uppercase font-mono">{currentMarketDef.code}</span>
              <span className="text-slate-400 text-[11px] font-mono">· {currentMarketDef.currency}</span>
              <ChevronDown className={`w-3.5 h-3.5 text-slate-400 transition-transform duration-150 ${isMarketOpen ? 'rotate-180 text-emerald-600' : ''}`} />
            </button>

            {/* Market Popover Dropdown */}
            {isMarketOpen && (
              <div className="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200 z-50 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div className="p-3 border-b border-slate-100 bg-slate-50">
                  <div className="relative">
                    <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                      type="text"
                      placeholder="Search markets (e.g. US, Germany, EUR, DKK)..."
                      value={marketSearch}
                      onChange={(e) => setMarketSearch(e.target.value)}
                      className="w-full bg-white border border-slate-200 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      autoFocus
                    />
                  </div>
                </div>

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
              </div>
            )}
          </div>

          {/* Mobile Menu Toggle Button */}
          <button
            type="button"
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="lg:hidden p-2 rounded-xl bg-slate-100 text-slate-700 hover:text-slate-900 hover:bg-slate-200 transition-colors"
            aria-label="Toggle Navigation Menu"
          >
            {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Mobile Drawer Navigation */}
      {isMobileMenuOpen && (
        <div className="lg:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-6 space-y-4 shadow-xl">
          {/* Mobile Search */}
          <form onSubmit={handleSearch}>
            <div className="relative">
              <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Search laptops, GPUs, phones..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-slate-100 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white"
              />
            </div>
          </form>

          {/* Quick Links */}
          <div className="grid grid-cols-3 gap-2 text-center text-xs font-semibold">
            <Link
              href={`/${currentMarket}`}
              className="py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800"
            >
              Home
            </Link>
            <Link
              href={`/${currentMarket}#deals`}
              className="py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800"
            >
              Deals
            </Link>
            <Link
              href={`/${currentMarket}/compare`}
              className="py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800"
            >
              Compare
            </Link>
          </div>

          {/* 20 Categories Grouped Accordion for Mobile */}
          <div className="space-y-3 pt-2">
            <h4 className="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono px-1">
              Shop 20 Categories
            </h4>
            <div className="space-y-2">
              {CATEGORY_GROUPS.map((group) => (
                <div key={group.name} className="bg-slate-50 rounded-xl p-3 border border-slate-200">
                  <div className="flex items-center gap-1.5 pb-2 font-bold text-xs text-slate-900 border-b border-slate-200/60">
                    {getGroupIcon(group.name)}
                    <span>{group.name}</span>
                  </div>
                  <div className="grid grid-cols-2 gap-1.5 pt-2 text-xs">
                    {group.categories.map((cat) => (
                      <Link
                        key={cat.slug}
                        href={`/${currentMarket}/categories/${cat.slug}`}
                        className="py-1 px-2 text-slate-600 hover:text-emerald-700 font-medium truncate"
                      >
                        {cat.name}
                      </Link>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}
    </header>
  );
};
