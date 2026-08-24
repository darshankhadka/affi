'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { detectBrowserMarket } from '@/lib/market-router';

export default function RootPage() {
  const router = useRouter();

  useEffect(() => {
    const targetMarket = detectBrowserMarket();
    router.replace(`/${targetMarket}`);
  }, [router]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50">
      <div className="text-center space-y-3">
        <div className="w-8 h-8 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto" />
        <p className="text-xs text-slate-500 font-medium font-mono">Routing to your regional tech engine...</p>
      </div>
    </div>
  );
}
