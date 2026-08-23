import React, { Suspense } from 'react';
import { Metadata } from 'next';
import { SearchClient } from './SearchClient';

interface SearchPageProps {
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
  title: 'Search Technology Catalog | ARIKARTECH',
  description: 'Search and compare tech hardware prices across verified stores.',
  robots: { index: false, follow: true }, // Prevent search query index bloat
};

export default async function SearchPage({ params }: SearchPageProps) {
  const { market } = await params;

  return (
    <Suspense
      fallback={
        <div className="space-y-8 animate-pulse">
          <div className="bg-white rounded-3xl p-8 border border-slate-200 h-32" />
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="bg-white rounded-2xl p-5 h-64 border border-slate-200" />
            ))}
          </div>
        </div>
      }
    >
      <SearchClient market={market} />
    </Suspense>
  );
}
