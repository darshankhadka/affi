import React from 'react';
import { Header } from '@/components/layout/Header';
import { Footer } from '@/components/layout/Footer';
import { fetchApi } from '@/lib/api';

interface MarketLayoutProps {
  children: React.ReactNode;
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

export default async function MarketLayout({ children, params }: MarketLayoutProps) {
  const { market } = await params;

  let markets = [
    { code: 'us', name: 'United States' },
    { code: 'uk', name: 'United Kingdom' },
    { code: 'de', name: 'Germany' },
    { code: 'fr', name: 'France' },
    { code: 'es', name: 'Spain' },
    { code: 'it', name: 'Italy' },
    { code: 'nl', name: 'Netherlands' },
    { code: 'au', name: 'Australia' },
    { code: 'nz', name: 'New Zealand' },
  ];

  let categories = [
    { name: 'Laptops', slug: 'laptops' },
    { name: 'GPUs', slug: 'gpus' },
    { name: 'CPUs', slug: 'cpus' },
    { name: 'Smartphones', slug: 'smartphones' },
    { name: 'Monitors', slug: 'monitors' },
    { name: 'SSDs', slug: 'ssds' },
    { name: 'RAM', slug: 'ram' },
  ];

  try {
    const [marketsData, categoriesData] = await Promise.all([
      fetchApi('/markets').catch(() => null),
      fetchApi('/categories').catch(() => null),
    ]);

    if (marketsData?.data) markets = marketsData.data;
    if (categoriesData?.data) categories = categoriesData.data;
  } catch {
    // Graceful fallback to default market definitions
  }

  return (
    <div className="flex flex-col min-h-screen">
      <Header currentMarket={market} markets={markets} categories={categories} />
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>
      <Footer currentMarket={market} />
    </div>
  );
}
