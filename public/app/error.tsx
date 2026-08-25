'use client';

import React, { useEffect } from 'react';
import Link from 'next/link';
import { BrandLogo } from '@/components/ui/BrandLogo';
import { RefreshCw, ArrowLeft } from 'lucide-react';

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log sanitized error message without exposing credentials or internal traces
    console.error('[ARIKARTECH Client Boundary]', error.message);
  }, [error]);

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-between p-6 sm:p-12">
      <header className="max-w-7xl mx-auto w-full flex items-center justify-between">
        <Link href="/" aria-label="ARIKARTECH Home">
          <BrandLogo className="h-10 w-auto" width={250} height={100} priority />
        </Link>
      </header>

      <main className="max-w-md mx-auto text-center space-y-6 my-auto py-12">
        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold">
          <span>Temporary Connection Disruption</span>
        </div>

        <h1 className="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
          We&apos;re Refreshing Our Data Feeds
        </h1>

        <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
          An unexpected error occurred while loading this page. Our engineers have been alerted and price indexes are being refreshed.
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <button
            type="button"
            onClick={() => reset()}
            className="w-full sm:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
          >
            <RefreshCw className="w-4 h-4" />
            <span>Try Again</span>
          </button>
          <Link
            href="/us"
            className="w-full sm:w-auto px-6 py-3 rounded-xl bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold text-xs transition-colors flex items-center justify-center gap-2"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>Return to Catalog</span>
          </Link>
        </div>
      </main>

      <footer className="max-w-7xl mx-auto w-full text-center text-xs text-slate-400 pt-8 border-t border-slate-200">
        © {new Date().getFullYear()} ARIKARTECH. All rights reserved.
      </footer>
    </div>
  );
}
