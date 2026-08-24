import React from 'react';
import { Metadata } from 'next';
import { Shield } from 'lucide-react';

import { CANONICAL_MARKETS } from '@/lib/catalog';

interface PrivacyPageProps {
  params: Promise<{ market: string }>;
}

export const dynamicParams = false;

export async function generateStaticParams() {
  return CANONICAL_MARKETS.map((m) => ({ market: m.code }));
}

export const metadata: Metadata = {
  title: 'Privacy Policy | ARIKARTECH',
  description: 'Our commitment to privacy, GDPR compliance, and non-invasive shopping analytics.',
};

export default async function PrivacyPage({ params }: PrivacyPageProps) {
  await params;

  return (
    <div className="max-w-4xl mx-auto space-y-12 py-4">
      <div className="space-y-4 text-center">
        <span className="px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-bold uppercase tracking-wider">
          Compliance & Safety
        </span>
        <h1 className="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight">
          Privacy Policy
        </h1>
        <p className="text-sm text-slate-500">Last updated: August 2026</p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-10 border border-slate-200 shadow-sm space-y-6 text-sm text-slate-700 leading-relaxed">
        <div className="flex items-center gap-2 text-emerald-700 font-bold text-base">
          <Shield className="w-5 h-5" /> Privacy by Design
        </div>
        <p>
          At ARIKARTECH, we value user trust and data privacy. We do not sell personal data, do not require user registration to compare tech prices, and do not track users across unauthorized third-party domains.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">1. Information We Process</h2>
        <p>
          When you browse ARIKARTECH or search for products, we collect anonymous usage telemetry to ensure platform availability and detect technical anomalies. Search queries and outbound referral clicks are recorded to optimize catalog discovery.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">2. Cryptographic IP Hashing</h2>
        <p>
          To prevent click fraud while respecting user anonymity, visitor IP addresses are immediately hashed using one-way SHA-256 cryptographic salts before being stored for click verification. We never store raw IP addresses in our referral analytics databases.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">3. Affiliate Links & Cookies</h2>
        <p>
          When you click on a retailer deal (e.g. &quot;BUY AT BEST PRICE&quot;), you are securely redirected to the merchant&apos;s website. The merchant or affiliate network may set standard tracking cookies on your device to attribute legitimate referral purchases.
        </p>

        <h2 className="text-lg font-bold text-slate-900 pt-3">4. GDPR & CCPA Compliance</h2>
        <p>
          Residents of the European Union, United Kingdom, and California enjoy rights to data transparency. Because ARIKARTECH operates without persistent user accounts or personally identifiable profiles, minimal non-identifiable telemetry is retained.
        </p>
      </div>
    </div>
  );
}
