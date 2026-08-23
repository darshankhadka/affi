import React from 'react';
import { Metadata } from 'next';
import { AlertCircle } from 'lucide-react';

interface DisclosurePageProps {
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
  title: 'Affiliate Disclosure | ARIKARTECH',
  description: 'FTC and ASA compliant affiliate relationship and advertising disclosure.',
};

export default async function DisclosurePage({ params }: DisclosurePageProps) {
  await params;

  return (
    <div className="max-w-4xl mx-auto space-y-12 py-4">
      <div className="space-y-4 text-center">
        <span className="px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-bold uppercase tracking-wider">
          Consumer Transparency
        </span>
        <h1 className="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
          Affiliate & Commercial Disclosure
        </h1>
        <p className="text-sm text-slate-500">Compliance: 16 CFR § 255.5 (FTC Guidelines) & ASA Standards</p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm space-y-6 text-sm text-slate-700 leading-relaxed">
        <div className="flex items-center gap-2 text-emerald-700 font-bold text-base">
          <AlertCircle className="w-5 h-5" /> How ARIKARTECH Earns Revenue
        </div>
        <p>
          ARIKARTECH is reader-supported. In order to operate high-frequency price crawling, maintain multi-market hardware catalogs, and provide free price comparison without charging users, we participate in authorized affiliate marketing programs.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">1. Affiliate Referral Links</h2>
        <p>
          When you click on retailer purchase buttons (such as &quot;BUY AT BEST PRICE&quot; or &quot;VIEW DEAL&quot;) and complete a purchase on a merchant website, ARIKARTECH may receive a small referral commission from the merchant or affiliate network at <strong>no extra cost to you</strong>.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">2. Editorial & Ranking Neutrality</h2>
        <p>
          Affiliate partnerships do not influence our technical specifications or price rankings. Our Best Price algorithms sort offers strictly by lowest verified price and in-stock status. Retailers cannot pay for preferential ranking or artificial price badges.
        </p>
      </div>
    </div>
  );
}
