import React from 'react';
import { NavLink } from 'react-router-dom';
import {
  LayoutDashboard,
  Box,
  FolderTree,
  Tag,
  GitMerge,
  BadgePercent,
  TrendingDown,
  History,
  Network,
  Store,
  LineChart,
  BarChart3,
  MousePointerClick,
  DollarSign,
  Search,
  BookOpen,
  Globe2,
  Cpu,
  Settings,
  Users,
  ShieldCheck,
  Flame,
  FileCode2,
} from 'lucide-react';
import clsx from 'clsx';

interface NavItem {
  name: string;
  href: string;
  icon: React.ReactNode;
}

interface NavSection {
  title: string;
  items: NavItem[];
}

export const Sidebar: React.FC = () => {
  const sections: NavSection[] = [
    {
      title: 'CORE',
      items: [
        { name: 'Dashboard', href: '/', icon: <LayoutDashboard className="w-4 h-4" /> },
      ],
    },
    {
      title: 'CATALOG',
      items: [
        { name: 'Products', href: '/catalog/products', icon: <Box className="w-4 h-4" /> },
        { name: 'Categories', href: '/catalog/categories', icon: <FolderTree className="w-4 h-4" /> },
        { name: 'Brands', href: '/catalog/brands', icon: <Tag className="w-4 h-4" /> },
        { name: 'Product Matching', href: '/catalog/matching', icon: <GitMerge className="w-4 h-4" /> },
      ],
    },
    {
      title: 'OFFERS',
      items: [
        { name: 'Offers', href: '/offers', icon: <BadgePercent className="w-4 h-4" /> },
        { name: 'Best Prices', href: '/offers/best-prices', icon: <TrendingDown className="w-4 h-4" /> },
        { name: 'Price History', href: '/offers/history', icon: <History className="w-4 h-4" /> },
      ],
    },
    {
      title: 'AFFILIATES',
      items: [
        { name: 'Providers', href: '/affiliates/providers', icon: <Network className="w-4 h-4" /> },
        { name: 'Retailers', href: '/affiliates/retailers', icon: <Store className="w-4 h-4" /> },
        { name: 'Performance', href: '/affiliates/performance', icon: <LineChart className="w-4 h-4" /> },
      ],
    },
    {
      title: 'ANALYTICS',
      items: [
        { name: 'Traffic', href: '/analytics/traffic', icon: <BarChart3 className="w-4 h-4" /> },
        { name: 'Affiliate Clicks', href: '/analytics/clicks', icon: <MousePointerClick className="w-4 h-4" /> },
        { name: 'Revenue', href: '/analytics/revenue', icon: <DollarSign className="w-4 h-4" /> },
      ],
    },
    {
      title: 'SEARCH',
      items: [
        { name: 'Search Overview', href: '/search', icon: <Search className="w-4 h-4" /> },
      ],
    },
    {
      title: 'CONTENT',
      items: [
        { name: 'Buying Guides & Deals', href: '/content/guides', icon: <BookOpen className="w-4 h-4" /> },
      ],
    },
    {
      title: 'SEO',
      items: [
        { name: 'SEO & Redirects', href: '/seo', icon: <Globe2 className="w-4 h-4" /> },
      ],
    },
    {
      title: 'AUTOMATION',
      items: [
        { name: 'Status & Jobs', href: '/automation', icon: <Cpu className="w-4 h-4" /> },
      ],
    },
    {
      title: 'SETTINGS',
      items: [
        { name: 'General & Markets', href: '/settings', icon: <Settings className="w-4 h-4" /> },
        { name: 'Users & Roles', href: '/settings/users', icon: <Users className="w-4 h-4" /> },
      ],
    },
  ];

  return (
    <aside className="w-64 shrink-0 bg-slate-950/90 border-r border-slate-800/80 flex flex-col h-screen overflow-y-auto">
      {/* Brand Header */}
      <div className="p-6 border-b border-slate-800/80 flex items-center gap-3">
        <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-black text-slate-950 shadow-lg shadow-emerald-500/20">
          A
        </div>
        <div>
          <h1 className="font-bold text-sm tracking-wider text-slate-100 uppercase">ARIKARTECH</h1>
          <p className="text-[10px] text-emerald-400 font-mono font-medium">CONTROL HUB</p>
        </div>
      </div>

      {/* Navigation Links */}
      <div className="px-4 py-6 space-y-6 flex-1">
        {sections.map((section, idx) => (
          <div key={idx} className="space-y-1">
            <h2 className="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
              {section.title}
            </h2>
            <div className="space-y-0.5 pt-1">
              {section.items.map((item) => (
                <NavLink
                  key={item.href}
                  to={item.href}
                  end={item.href === '/'}
                  className={({ isActive }) =>
                    clsx(
                      'flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-xl transition-colors',
                      isActive
                        ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold'
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'
                    )
                  }
                >
                  <span className="shrink-0">{item.icon}</span>
                  <span>{item.name}</span>
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </div>
    </aside>
  );
};
