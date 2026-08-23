import React from 'react';
import { Metadata } from 'next';
import { AlertCircle, CheckCircle2, DollarSign } from 'lucide-react';

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: 'Affiliate Disclosure & Transparency | ARIKARTECH',
    description: 'FTC and ASA compliant affiliate disclosure explaining how ARIKARTECH earns revenue.',
  };
}

export default async function DisclosurePage() {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-4">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <DollarSign className="w-3.5 h-3.5" />
          <span>Transparency & Compliance</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          Affiliate Transparency Disclosure
        </h1>
        <p className="text-xs text-slate-500 font-mono">
          In Compliance with FTC 16 CFR § 255.5 and Global Advertising Standards
        </p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-8 text-xs text-slate-600 leading-relaxed">
        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
            1. How ARIKARTECH Earns Revenue
          </h2>
          <p>
            ARIKARTECH is a free technology discovery and price comparison engine. To support our server infrastructure, high-speed price synchronization engines, and engineering development, we participate in authorized affiliate marketing programs.
          </p>
          <p>
            When you click a "BUY AT BEST PRICE" or "VIEW DEAL" link on our website and proceed to complete a purchase on the merchant’s external store, ARIKARTECH may receive a small referral commission from the retailer at no additional cost to you.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <AlertCircle className="w-4 h-4 text-emerald-600" />
            2. Editorial Independence & Ranking Integrity
          </h2>
          <p>
            Our comparison tables rank store offers purely on verified numeric price (including in-stock availability priority). Retailer affiliate partnerships do not influence which store is calculated as the "Best Price We Found". We never fabricate artificial discounts, promote misleading availability, or prioritize higher-commission offers over lower consumer prices.
          </p>
        </section>
      </div>
    </div>
  );
}
