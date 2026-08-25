import React from 'react';
import { CANONICAL_MARKETS, getMarketDef } from '@/lib/catalog';
import { StructuredData } from '@/components/seo/StructuredData';
import { HomeDealsClient } from './HomeDealsClient';

interface MarketHomePageProps {
  params: Promise<{ market: string }>;
}

export const dynamicParams = false;

export async function generateStaticParams() {
  return CANONICAL_MARKETS.map((m) => ({ market: m.code }));
}

export default async function MarketHomePage({ params }: MarketHomePageProps) {
  const { market } = await params;
  const marketInfo = getMarketDef(market);

  const websiteSchema = {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: 'ARIKARTECH',
    url: `https://arikartech.com/${market}`,
    potentialAction: {
      '@type': 'SearchAction',
      target: `https://arikartech.com/${market}/search?q={search_term_string}`,
      'query-input': 'required name=search_term_string',
    },
  };

  return (
    <div className="space-y-12">
      <StructuredData data={websiteSchema} />
      <HomeDealsClient market={market} />
    </div>
  );
}
