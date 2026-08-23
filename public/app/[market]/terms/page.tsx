import React from 'react';
import { Metadata } from 'next';
import { FileCheck, AlertTriangle, HelpCircle } from 'lucide-react';

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: 'Terms of Service | ARIKARTECH',
    description: 'Terms of service and usage conditions for the ARIKARTECH comparison platform.',
  };
}

export default async function TermsPage() {
  return (
    <div className="max-w-4xl mx-auto space-y-8">
      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-4">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <FileCheck className="w-3.5 h-3.5" />
          <span>Legal Agreement</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          Terms of Service
        </h1>
        <p className="text-xs text-slate-500 font-mono">
          Last Updated: August 23, 2026
        </p>
      </div>

      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-8 text-xs text-slate-600 leading-relaxed">
        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <FileCheck className="w-4 h-4 text-emerald-600" />
            1. Informational Service Only
          </h2>
          <p>
            ARIKARTECH provides hardware price comparison and specifications aggregation for informational purposes. While our automated ingestion engines continuously synchronize real-time pricing feeds from authorized merchants, prices, inventory availability, and shipping costs are subject to change on the merchant website at any time without notice.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 text-amber-600" />
            2. Retailer Transactions & Fulfillment Disclaimer
          </h2>
          <p>
            ARIKARTECH is not an online store or marketplace seller. We do not sell products, accept customer payments, hold inventory, process orders, or fulfill deliveries. All purchases initiated through referral links on this platform occur entirely on the third-party merchant website under their respective sale terms, warranties, and return policies.
          </p>
        </section>

        <section className="space-y-3">
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <HelpCircle className="w-4 h-4 text-emerald-600" />
            3. Limitation of Liability
          </h2>
          <p>
            ARIKARTECH and its operators shall not be liable for any discrepancies in pricing, defective merchandise, shipping delays, or transactional disputes arising between you and any third-party retailer linked from our platform.
          </p>
        </section>
      </div>
    </div>
  );
}
