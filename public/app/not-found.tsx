import React from 'react';
import Link from 'next/link';
import { BrandLogo } from '@/components/ui/BrandLogo';
import { ArrowLeft, Search, Layers } from 'lucide-react';

export default function NotFound() {
  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-between p-6 sm:p-12">
      <header className="max-w-7xl mx-auto w-full flex items-center justify-between">
        <Link href="/" aria-label="ARIKARTECH Home">
          <BrandLogo className="h-10 w-auto" width={250} height={100} priority />
        </Link>
      </header>

      <main className="max-w-xl mx-auto text-center space-y-6 my-auto py-12">
        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
          <span>Error 404 · Page Not Found</span>
        </div>

        <h1 className="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight">
          Looking for Technology Specs or Deals?
        </h1>

        <p className="text-sm text-slate-600 leading-relaxed">
          The requested page or product route could not be found or has moved. Explore hardware categories or browse the global price engine below.
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <Link
            href="/us"
            className="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-2"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>Go to Catalog Home</span>
          </Link>
          <Link
            href="/us/categories/laptops"
            className="w-full sm:w-auto px-6 py-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold text-xs transition-colors flex items-center justify-center gap-2"
          >
            <Layers className="w-4 h-4 text-emerald-600" />
            <span>Explore Hardware Categories</span>
          </Link>
        </div>
      </main>

      <footer className="max-w-7xl mx-auto w-full text-center text-xs text-slate-400 pt-8 border-t border-slate-200">
        © {new Date().getFullYear()} ARIKARTECH. Global Technology Price Comparison Engine.
      </footer>
    </div>
  );
}
