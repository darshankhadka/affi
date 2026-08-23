'use client';

import React from 'react';
import { ExternalLink, CheckCircle2, XCircle, Clock, ShieldCheck, Tag } from 'lucide-react';
import { trackAffiliateClick } from '../seo/GoogleAnalytics';

export interface RetailerOffer {
  id: number;
  retailer?: {
    id: number;
    name: string;
    domain: string;
    logo_url?: string | null;
  };
  currency?: {
    code: string;
    symbol: string;
  };
  price: number;
  original_price?: number | null;
  shipping_cost?: number | null;
  availability: 'in_stock' | 'out_of_stock' | 'preorder' | 'discontinued';
  condition: 'new' | 'refurbished' | 'used';
  title: string;
  affiliate_url: string;
}

interface OfferComparisonTableProps {
  offers: RetailerOffer[];
  productName: string;
}

export const OfferComparisonTable: React.FC<OfferComparisonTableProps> = ({ offers, productName }) => {
  if (!offers || offers.length === 0) {
    return (
      <div className="glass-card rounded-2xl p-8 text-center border border-dashed border-slate-800">
        <p className="text-sm font-semibold text-slate-300">No store offers available in this market yet.</p>
        <p className="text-xs text-slate-400 mt-1">
          Retailer availability and price feeds for this country market are currently updating.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {offers.map((offer, idx) => {
        const currencySymbol = offer.currency?.symbol || '$';
        const currencyCode = offer.currency?.code || 'USD';
        const retailerName = offer.retailer?.name || 'Retailer';
        const isBestDeal = idx === 0 && offer.availability === 'in_stock';
        const redirectUrl = `/api/out/${offer.id}`;

        const handleAffiliateClick = () => {
          trackAffiliateClick(offer.id, productName, retailerName, offer.price, currencyCode);
        };

        return (
          <div
            key={offer.id}
            className={`glass-card rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all duration-200 hover:border-slate-700/80 ${
              isBestDeal ? 'border-emerald-500/40 bg-emerald-950/10 shadow-lg shadow-emerald-500/5' : ''
            }`}
          >
            {/* Store & Product info */}
            <div className="flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center font-bold text-slate-200 text-sm shrink-0">
                {retailerName.charAt(0)}
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <h4 className="font-bold text-sm text-slate-100">{retailerName}</h4>
                  {isBestDeal && (
                    <span className="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                      Best Price
                    </span>
                  )}
                </div>
                <p className="text-xs text-slate-400 mt-0.5 line-clamp-1">{offer.title}</p>
                <div className="flex items-center gap-3 mt-1 text-[11px] text-slate-400">
                  <span className="capitalize">{offer.condition} condition</span>
                  {offer.shipping_cost !== null && offer.shipping_cost !== undefined && (
                    <span>
                      · Shipping: {offer.shipping_cost === 0 ? 'Free' : `${currencySymbol}${offer.shipping_cost.toFixed(2)}`}
                    </span>
                  )}
                </div>
              </div>
            </div>

            {/* Price & View Deal Button */}
            <div className="flex items-center justify-between sm:justify-end gap-6 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-800/60">
              <div className="text-left sm:text-right">
                <span className="text-xl sm:text-2xl font-black text-slate-100 block leading-tight">
                  {currencySymbol}{Number(offer.price).toFixed(2)}
                </span>
                <div className="flex items-center gap-1 mt-0.5">
                  {offer.availability === 'in_stock' ? (
                    <span className="text-[11px] text-emerald-400 flex items-center gap-1 font-medium">
                      <CheckCircle2 className="w-3 h-3" /> In Stock
                    </span>
                  ) : (
                    <span className="text-[11px] text-rose-400 flex items-center gap-1 font-medium">
                      <XCircle className="w-3 h-3" /> Out of Stock
                    </span>
                  )}
                </div>
              </div>

              <a
                href={redirectUrl}
                target="_blank"
                rel="nofollow sponsored noopener"
                onClick={handleAffiliateClick}
                className="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 transition-all hover:scale-[1.02] shrink-0"
              >
                <span>View Deal</span>
                <ExternalLink className="w-3.5 h-3.5" />
              </a>
            </div>
          </div>
        );
      })}
    </div>
  );
};
