import React from 'react';
import { Metadata } from 'next';
import { ShieldCheck, Cpu, Layers, ExternalLink } from 'lucide-react';
import Link from 'next/link';

import { CANONICAL_MARKETS } from '@/lib/catalog';

interface AboutPageProps {
  params: Promise<{ market: string }>;
}

export const dynamicParams = false;

export async function generateStaticParams() {
  return CANONICAL_MARKETS.map((m) => ({ market: m.code }));
}

export const metadata: Metadata = {
  title: 'About ARIKARTECH | Global Technology Discovery & Price Comparison',
  description: 'Learn about the mission, architecture, and technology shopping methodology behind ARIKARTECH.',
};

export default async function AboutPage({ params }: AboutPageProps) {
  const { market } = await params;

  return (
    <div className="max-w-4xl mx-auto space-y-12 py-4">
      <div className="space-y-4 text-center">
        <span className="px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-bold uppercase tracking-wider">
          Platform Mission
        </span>
        <h1 className="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
          About ARIKARTECH
        </h1>
        <p className="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
          Transparent, automated hardware comparison and shopping discovery across the world&apos;s leading technology retailers.
        </p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm space-y-6 text-sm text-slate-700 leading-relaxed">
        <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
          <Cpu className="w-5 h-5 text-emerald-600" /> What is ARIKARTECH?
        </h2>
        <p>
          ARIKARTECH is a pure technology discovery, specification index, and price-comparison engine. We are <strong>not</strong> an online store, warehouse, or marketplace seller. We do not process payments, manage physical inventory, or fulfill orders.
        </p>
        <p>
          Instead, our automated catalog intelligence continuously scans and indexes verified product feeds from authorized technology merchants across 9 regional markets (US, UK, DE, FR, ES, IT, NL, AU, NZ).
        </p>

        <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2 pt-4">
          <Layers className="w-5 h-5 text-emerald-600" /> One Canonical Product Model
        </h2>
        <p>
          Unlike uncurated marketplaces where the same laptop or processor is listed hundreds of times with misleading titles, ARIKARTECH unifies merchant listings under a single verified canonical product record using global identifiers (UPC, EAN, GTIN, MPN, ASIN).
        </p>

        <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2 pt-4">
          <ShieldCheck className="w-5 h-5 text-emerald-600" /> Editorial Independence
        </h2>
        <p>
          We believe consumers deserve 100% objective price comparison. Retailers cannot pay to artificially raise their price rank on our comparison tables. The <strong>Best Price</strong> badge is awarded solely to the lowest verified, in-stock offer at the moment of price refresh.
        </p>

        <div className="pt-6 border-t border-slate-100 flex flex-wrap gap-4">
          <Link
            href={`/${market}/disclosure`}
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline"
          >
            Affiliate Disclosure <ExternalLink className="w-3.5 h-3.5" />
          </Link>
          <Link
            href={`/${market}/privacy`}
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline"
          >
            Privacy Policy <ExternalLink className="w-3.5 h-3.5" />
          </Link>
          <Link
            href={`/${market}/contact`}
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 hover:underline"
          >
            Contact & Merchant Inquiries <ExternalLink className="w-3.5 h-3.5" />
          </Link>
        </div>
      </div>
    </div>
  );
}
