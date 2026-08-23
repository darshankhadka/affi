import React from 'react';
import Link from 'next/link';

interface FooterProps {
  currentMarket: string;
}

export const Footer: React.FC<FooterProps> = ({ currentMarket }) => {
  return (
    <footer className="border-t border-slate-800/80 bg-[#060911] text-slate-400 text-xs mt-24">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
          {/* Brand Col */}
          <div className="space-y-3 md:col-span-2">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-black text-slate-950">
                A
              </div>
              <span className="font-extrabold text-base tracking-wider text-slate-100 uppercase">ARIKARTECH</span>
            </div>
            <p className="text-xs text-slate-500 max-w-sm leading-relaxed">
              Global technology discovery, price-comparison, and hardware deal platform. We index and compare authorized tech retailers to find you verified prices in real-time.
            </p>
          </div>

          {/* Markets Col */}
          <div>
            <h4 className="font-semibold text-slate-200 uppercase tracking-wider text-[11px] mb-3">Target Markets</h4>
            <ul className="space-y-2 text-xs">
              <li><Link href="/us" className="hover:text-emerald-400 transition-colors">United States (US)</Link></li>
              <li><Link href="/uk" className="hover:text-emerald-400 transition-colors">United Kingdom (UK)</Link></li>
              <li><Link href="/de" className="hover:text-emerald-400 transition-colors">Germany (DE)</Link></li>
              <li><Link href="/fr" className="hover:text-emerald-400 transition-colors">France (FR)</Link></li>
              <li><Link href="/es" className="hover:text-emerald-400 transition-colors">Spain (ES)</Link></li>
            </ul>
          </div>

          {/* Legal Col */}
          <div>
            <h4 className="font-semibold text-slate-200 uppercase tracking-wider text-[11px] mb-3">Platform Information</h4>
            <ul className="space-y-2 text-xs">
              <li><span className="text-slate-500">Privacy Policy</span></li>
              <li><span className="text-slate-500">Terms of Service</span></li>
              <li><span className="text-slate-500">Affiliate Disclosure</span></li>
            </ul>
          </div>
        </div>

        {/* Mandatory Affiliate FTC / ASA Compliance Disclosure */}
        <div className="pt-8 border-t border-slate-800/60 space-y-4 text-[11px] text-slate-400 leading-relaxed">
          <p>
            <strong className="text-slate-400 font-semibold">Affiliate Transparency Disclosure:</strong> ARIKARTECH is an independent technology price comparison engine. We may earn a commission when you click retailer referral links on our platform and make a qualifying purchase. This does not impact our pricing indexes or product rankings. ARIKARTECH is not an ecommerce retailer and does not handle order processing, fulfillment, or shipping. Transactions occur directly with the respective retailer.
          </p>
          <p className="text-slate-400">
            © {new Date().getFullYear()} ARIKARTECH. All rights reserved. Registered trademarks belong to their respective owners.
          </p>
        </div>
      </div>
    </footer>
  );
};
