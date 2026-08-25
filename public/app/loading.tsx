import React from 'react';
import { BrandLogo } from '@/components/ui/BrandLogo';

export default function Loading() {
  return (
    <div className="min-h-screen bg-slate-50 flex flex-col justify-center items-center p-6">
      <div className="flex flex-col items-center gap-4 animate-pulse">
        <BrandLogo className="h-12 w-auto opacity-80" width={250} height={100} priority />
        <div className="flex items-center gap-2 text-xs font-mono text-slate-400">
          <div className="w-2 h-2 rounded-full bg-emerald-500 animate-ping" />
          <span>Indexing Live Technology Prices...</span>
        </div>
      </div>
    </div>
  );
}
