import React from 'react';
import Link from 'next/link';
import { BrandLogo } from '@/components/ui/BrandLogo';
import { ArrowRight } from 'lucide-react';

interface FooterProps {
  currentMarket: string;
}

export const Footer: React.FC<FooterProps> = ({ currentMarket }) => {
  return (
    <footer className="border-t border-slate-200 bg-white text-slate-600 text-xs mt-24">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
          
          {/* Brand Col */}
          <div className="space-y-3 md:col-span-1">
            <Link
              href={`/${currentMarket}`}
              className="inline-block hover:opacity-95 transition-opacity"
              aria-label="ARIKARTECH Home"
            >
              <BrandLogo className="h-9 w-auto" width={250} height={100} priority={false} />
            </Link>
            <p className="text-xs text-slate-500 max-w-sm leading-relaxed">
              Global technology discovery and price-comparison engine. We index and compare authorized tech retailers across 35 international markets.
            </p>
          </div>

          {/* Shop Column */}
          <div>
            <h4 className="font-bold text-slate-900 uppercase tracking-wider text-[11px] font-mono mb-3">
              Shop Tech
            </h4>
            <ul className="space-y-2 text-xs text-slate-600">
              <li>
                <Link href={`/${currentMarket}/categories/laptops`} className="hover:text-emerald-700 transition-colors">
                  Laptops
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/categories/gaming-laptops`} className="hover:text-emerald-700 transition-colors">
                  Gaming Laptops
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/categories/smartphones`} className="hover:text-emerald-700 transition-colors">
                  Smartphones
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/categories/gpus-graphics-cards`} className="hover:text-emerald-700 transition-colors">
                  GPUs & Graphics Cards
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/categories/gaming-monitors`} className="hover:text-emerald-700 transition-colors">
                  Gaming Monitors
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/categories/headphones-audio`} className="hover:text-emerald-700 transition-colors">
                  Headphones & Audio
                </Link>
              </li>
              <li className="pt-1">
                <Link href={`/${currentMarket}/search`} className="text-emerald-700 font-bold hover:text-emerald-800 flex items-center gap-1">
                  View all 20 categories <ArrowRight className="w-3 h-3" />
                </Link>
              </li>
            </ul>
          </div>

          {/* Company Column */}
          <div>
            <h4 className="font-bold text-slate-900 uppercase tracking-wider text-[11px] font-mono mb-3">
              Company
            </h4>
            <ul className="space-y-2 text-xs text-slate-600">
              <li>
                <Link href={`/${currentMarket}/about`} className="hover:text-emerald-700 transition-colors">
                  About Us
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/contact`} className="hover:text-emerald-700 transition-colors">
                  Contact Us
                </Link>
              </li>
            </ul>
          </div>

          {/* Legal Column */}
          <div>
            <h4 className="font-bold text-slate-900 uppercase tracking-wider text-[11px] font-mono mb-3">
              Legal & Compliance
            </h4>
            <ul className="space-y-2 text-xs text-slate-600">
              <li>
                <Link href={`/${currentMarket}/privacy`} className="hover:text-emerald-700 transition-colors">
                  Privacy Policy
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/terms`} className="hover:text-emerald-700 transition-colors">
                  Terms of Service
                </Link>
              </li>
              <li>
                <Link href={`/${currentMarket}/disclosure`} className="hover:text-emerald-700 transition-colors">
                  Affiliate Disclosure
                </Link>
              </li>
            </ul>
          </div>
        </div>

        {/* Clear & Explicit Affiliate Disclosure (Section 38) */}
        <div className="pt-8 border-t border-slate-100 space-y-3 text-[11px] text-slate-500 leading-relaxed">
          <p>
            <strong className="text-slate-700 font-semibold">Affiliate Disclosure:</strong> Some links on ARIKARTECH are affiliate links. We may earn a commission when you purchase through them, at no additional cost to you. Pricing and store availability are synchronized in real-time with authorized retailers.
          </p>
          <p className="text-slate-400">
            © {new Date().getFullYear()} ARIKARTECH. All rights reserved. Registered trademarks belong to their respective owners.
          </p>
        </div>
      </div>
    </footer>
  );
};
