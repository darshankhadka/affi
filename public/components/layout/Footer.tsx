import React from 'react';
import Link from 'next/link';
import { BrandLogo } from '@/components/ui/BrandLogo';

interface FooterProps {
  currentMarket: string;
}

export const Footer: React.FC<FooterProps> = ({ currentMarket }) => {
  return (
    <footer className="border-t border-slate-200 bg-white text-slate-600 text-xs mt-24">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
          {/* Brand Col */}
          <div className="space-y-3 md:col-span-2">
            <Link href={`/${currentMarket}`} className="inline-block hover:opacity-95 transition-opacity" aria-label="ARIKARTECH Home">
              <BrandLogo className="h-9 w-auto" width={250} height={100} priority={false} />
            </Link>
            <p className="text-xs text-slate-500 max-w-sm leading-relaxed">
              Global technology discovery, price-comparison, and hardware deal platform. We index and compare authorized tech retailers across 35 international markets in real-time.
            </p>
          </div>

          {/* Markets Col */}
          <div>
            <h4 className="font-semibold text-slate-900 uppercase tracking-wider text-[11px] mb-3">Key Global Markets</h4>
            <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
              <Link href="/us" className="hover:text-emerald-600 transition-colors">🇺🇸 United States</Link>
              <Link href="/ca" className="hover:text-emerald-600 transition-colors">🇨🇦 Canada</Link>
              <Link href="/gb" className="hover:text-emerald-600 transition-colors">🇬🇧 United Kingdom</Link>
              <Link href="/de" className="hover:text-emerald-600 transition-colors">🇩🇪 Germany</Link>
              <Link href="/fr" className="hover:text-emerald-600 transition-colors">🇫🇷 France</Link>
              <Link href="/es" className="hover:text-emerald-600 transition-colors">🇪🇸 Spain</Link>
              <Link href="/it" className="hover:text-emerald-600 transition-colors">🇮🇹 Italy</Link>
              <Link href="/nl" className="hover:text-emerald-600 transition-colors">🇳🇱 Netherlands</Link>
              <Link href="/se" className="hover:text-emerald-600 transition-colors">🇸🇪 Sweden</Link>
              <Link href="/dk" className="hover:text-emerald-600 transition-colors">🇩🇰 Denmark</Link>
              <Link href="/pl" className="hover:text-emerald-600 transition-colors">🇵🇱 Poland</Link>
              <Link href="/ch" className="hover:text-emerald-600 transition-colors">🇨🇭 Switzerland</Link>
              <Link href="/no" className="hover:text-emerald-600 transition-colors">🇳🇴 Norway</Link>
              <Link href="/au" className="hover:text-emerald-600 transition-colors">🇦🇺 Australia</Link>
              <Link href="/nz" className="hover:text-emerald-600 transition-colors">🇳🇿 New Zealand</Link>
            </div>
          </div>

          {/* Legal Col */}
          <div>
            <h4 className="font-semibold text-slate-900 uppercase tracking-wider text-[11px] mb-3">Platform Information</h4>
            <ul className="space-y-2 text-xs text-slate-500">
              <li>
                <Link href={`/${currentMarket}/about`} className="hover:text-emerald-600 transition-colors">
                  About Us
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/privacy`} className="hover:text-emerald-600 transition-colors">
                  Privacy Policy
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/terms`} className="hover:text-emerald-600 transition-colors">
                  Terms of Service
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/disclosure`} className="hover:text-emerald-600 transition-colors">
                  Affiliate Disclosure
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/contact`} className="hover:text-emerald-600 transition-colors">
                  Contact Us
                </Link>
              </li>
            </ul>
          </div>
        </div>

        {/* Mandatory Affiliate FTC / ASA Compliance Disclosure */}
        <div className="pt-8 border-t border-slate-100 space-y-4 text-[11px] text-slate-500 leading-relaxed">
          <p>
            <strong className="text-slate-700 font-semibold">Affiliate Transparency Disclosure:</strong> ARIKARTECH is an independent technology price comparison engine. We may earn a commission when you click retailer referral links on our platform and make a qualifying purchase. This does not impact our pricing indexes or product rankings. ARIKARTECH is not an ecommerce retailer and does not handle order processing, fulfillment, or shipping. Transactions occur directly with the respective retailer.
          </p>
          <p className="text-slate-400">
            © {new Date().getFullYear()} ARIKARTECH. All rights reserved. Registered trademarks belong to their respective owners.
          </p>
        </div>
      </div>
    </footer>
  );
};
