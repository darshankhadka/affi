import React from 'react';
import { Metadata } from 'next';
import { FileText } from 'lucide-react';

import { CANONICAL_MARKETS } from '@/lib/catalog';

interface TermsPageProps {
  params: Promise<{ market: string }>;
}

export const dynamicParams = false;

export async function generateStaticParams() {
  return CANONICAL_MARKETS.map((m) => ({ market: m.code }));
}

export const metadata: Metadata = {
  title: 'Terms of Service | ARIKARTECH',
  description: 'Terms and conditions for utilizing the ARIKARTECH technology comparison engine.',
};

export default async function TermsPage({ params }: TermsPageProps) {
  await params;

  return (
    <div className="max-w-4xl mx-auto space-y-12 py-4">
      <div className="space-y-4 text-center">
        <span className="px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-bold uppercase tracking-wider">
          Legal Agreement
        </span>
        <h1 className="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
          Terms of Service
        </h1>
        <p className="text-sm text-slate-500">Last updated: August 2026</p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm space-y-6 text-sm text-slate-700 leading-relaxed">
        <div className="flex items-center gap-2 text-emerald-700 font-bold text-base">
          <FileText className="w-5 h-5" /> Terms of Platform Use
        </div>
        <p>
          By accessing or using ARIKARTECH (&quot;the Platform&quot;), you agree to be bound by these Terms of Service. If you disagree with any portion of these terms, please discontinue use of the platform.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">1. Nature of the Service</h2>
        <p>
          ARIKARTECH is a technical search engine and price comparison service for computer hardware, consumer electronics, and accessories. We do not sell, warranty, or ship any products listed on this website.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">2. Pricing Accuracy & Disclaimer</h2>
        <p>
          Retailer prices, shipping rates, and product availability are subject to rapid change by external merchants. While our ingestion pipeline continuously refreshes pricing data, the final price and purchase terms displayed on the retailer&apos;s checkout page govern all transactions.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">3. External Merchant Relationships</h2>
        <p>
          Transactions executed with third-party retailers are solely between you and the respective retailer. ARIKARTECH bears no responsibility or liability for product defects, delivery delays, customer service disputes, or billing issues resulting from third-party purchases.
        </p>
      </div>
    </div>
  );
}
