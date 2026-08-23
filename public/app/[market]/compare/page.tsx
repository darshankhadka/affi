import React, { Suspense } from 'react';
import { Metadata } from 'next';
import { CompareClient } from './CompareClient';

interface ComparePageProps {
  params: Promise<{ market: string }>;
}

export async function generateStaticParams() {
  return [
    { market: 'us' },
    { market: 'uk' },
    { market: 'de' },
    { market: 'fr' },
    { market: 'es' },
    { market: 'it' },
    { market: 'nl' },
    { market: 'au' },
    { market: 'nz' },
  ];
}

export const metadata: Metadata = {
  title: 'Compare Hardware Specs & Retailer Prices | ARIKARTECH',
  description: 'Side-by-side technical hardware specifications and multi-store price comparisons.',
};

export default async function ComparePage({ params }: ComparePageProps) {
  const { market } = await params;

  return (
    <Suspense
      fallback={
        <div className="space-y-8 animate-pulse">
          <div className="bg-white rounded-3xl p-8 border border-slate-200 h-32" />
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3].map((i) => (
              <div key={i} className="bg-white rounded-2xl p-6 h-64 border border-slate-200" />
            ))}
          </div>
        </div>
      }
    >
      <CompareClient market={market} />
    </Suspense>
  );
}
