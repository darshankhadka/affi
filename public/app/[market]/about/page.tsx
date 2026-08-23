import React from 'react';
import { Metadata } from 'next';
import { Info, ShieldCheck, Cpu, Database, Zap } from 'lucide-react';

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: 'About ARIKARTECH | Global Technology Shopping Discovery & Price Comparison',
    description: 'Learn how ARIKARTECH helps shoppers discover technology hardware and compare verified store offers in real time.',
  };
}

export default async function AboutPage() {
  return (
    <div className="max-w-4xl mx-auto space-y-12">
      {/* Header */}
      <div className="bg-white rounded-3xl p-8 sm:p-12 border border-slate-200 shadow-sm space-y-4">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Info className="w-3.5 h-3.5" />
          <span>Platform Mission</span>
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
          About ARIKARTECH
        </h1>
        <p className="text-sm text-slate-600 leading-relaxed max-w-2xl">
          ARIKARTECH is a high-speed, independent technology shopping discovery and price-comparison platform. Our mission is to provide hardware enthusiasts and everyday buyers with transparent, real-time price transparency across authorized retailers worldwide.
        </p>
      </div>

      {/* Core Principles */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
          <div className="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
            <Cpu className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-slate-900 text-base">Canonical Hardware Model</h3>
          <p className="text-xs text-slate-500 leading-relaxed">
            We map fragmented merchant listings into unified canonical product entities using global identifiers (GTIN, EAN, UPC, ASIN, and MPN).
          </p>
        </div>

        <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
          <div className="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold">
            <Database className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-slate-900 text-base">Real Data Only</h3>
          <p className="text-xs text-slate-500 leading-relaxed">
            Zero fabricated prices, zero fake reviews, and zero deceptive countdown urgency. We display authentic inventory feeds from verified merchants.
          </p>
        </div>

        <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
          <div className="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
            <Zap className="w-5 h-5" />
          </div>
          <h3 className="font-bold text-slate-900 text-base">Shared-Hosting Efficiency</h3>
          <p className="text-xs text-slate-500 leading-relaxed">
            Engineered with high-speed caching and bounded synchronization routines to deliver sub-100ms comparison pages across 9 global markets.
          </p>
        </div>
      </div>

      {/* Business Model Note */}
      <div className="bg-slate-50 rounded-2xl p-6 border border-slate-200 text-xs text-slate-600 leading-relaxed space-y-2">
        <h4 className="font-semibold text-slate-900 flex items-center gap-2">
          <ShieldCheck className="w-4 h-4 text-emerald-600" />
          How ARIKARTECH Works
        </h4>
        <p>
          ARIKARTECH is purely a price comparison and discovery engine. We do not operate warehouses, manage inventory, process customer payments, or fulfill shipments. When you find the best price for a laptop, graphics card, or smartphone on ARIKARTECH, you click directly to the authorized merchant who handles the checkout and fulfillment.
        </p>
      </div>
    </div>
  );
}
