import React from 'react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { CANONICAL_MARKETS } from '@/lib/catalog';

interface MarketLayoutProps {
  children: React.ReactNode;
  params: Promise<{ market: string }>;
}

export const dynamicParams = false;

export async function generateStaticParams() {
  return CANONICAL_MARKETS.map((m) => ({ market: m.code }));
}

export default async function MarketLayout({ children, params }: MarketLayoutProps) {
  const { market } = await params;

  return (
    <div className="flex flex-col min-h-screen">
      <Header currentMarket={market} markets={CANONICAL_MARKETS} />
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>
      <Footer currentMarket={market} />
    </div>
  );
}
