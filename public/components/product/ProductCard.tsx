'use client';

import React from 'react';
import Link from 'next/link';
import { ArrowRight, Laptop, ExternalLink, SlidersHorizontal } from 'lucide-react';
import { productPath } from '@/lib/url';
import { getOutboundUrl } from '@/lib/api';
import { trackAffiliateClick } from '@/components/seo/GoogleAnalytics';

interface ProductCardProps {
  product: {
    id: number;
    name: string;
    slug: string;
    model_number?: string | null;
    short_description?: string | null;
    primary_image?: { url: string; alt_text?: string } | null;
    brand?: { name: string; slug?: string } | null;
    category?: { name: string; slug?: string } | null;
    best_price?: {
      min_price: number;
      offer_count: number;
      currency?: { symbol: string; code?: string };
      best_offer_id?: number | null;
      best_offer?: {
        id: number;
        retailer?: { name: string };
      } | null;
    } | null;
  };
  market: string;
}

export const ProductCard: React.FC<ProductCardProps> = ({ product, market }) => {
  const currencySymbol = product.best_price?.currency?.symbol || '$';
  const minPrice = product.best_price?.min_price;
  const offerCount = product.best_price?.offer_count || 0;
  const bestOffer = product.best_price?.best_offer;
  const bestOfferId = product.best_price?.best_offer_id || bestOffer?.id;
  const retailerName = bestOffer?.retailer?.name;

  const handleDirectBuy = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (bestOfferId) {
      trackAffiliateClick(
        bestOfferId,
        product.name,
        retailerName || 'Retailer',
        minPrice || 0,
        product.best_price?.currency?.code || 'USD'
      );
    }
  };

  return (
    <div className="group bg-white rounded-2xl p-4 sm:p-5 flex flex-col justify-between border border-slate-200 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 relative overflow-hidden">
      <div>
        {/* Product Image */}
        <Link
          href={productPath(market, product.slug)}
          className="aspect-[4/3] rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-3 overflow-hidden relative block"
        >
          {product.primary_image?.url ? (
            <img
              src={product.primary_image.url}
              alt={product.primary_image.alt_text || product.name}
              className="object-contain w-full h-full p-3 group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="flex flex-col items-center justify-center text-slate-400 gap-2">
              <Laptop className="w-10 h-10 text-slate-300" />
              <span className="text-[10px] font-mono uppercase tracking-wider">{product.brand?.name || 'Hardware'}</span>
            </div>
          )}
        </Link>

        {/* Brand & Category Info */}
        <div className="flex items-center gap-2 mb-1.5">
          {product.brand && (
            <span className="text-[10px] font-bold uppercase tracking-wider text-emerald-800 font-mono bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60">
              {product.brand.name}
            </span>
          )}
          {product.category && (
            <span className="text-[11px] text-slate-500 truncate">{product.category.name}</span>
          )}
        </div>

        {/* Product Title */}
        <Link href={productPath(market, product.slug)}>
          <h3 className="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-snug">
            {product.name}
          </h3>
        </Link>

        {/* Key spec / short description summary */}
        {product.short_description && (
          <p className="text-[11px] text-slate-500 mt-1 line-clamp-1 leading-relaxed">
            {product.short_description}
          </p>
        )}
      </div>

      {/* Pricing, Retailer & Direct Actions Footer */}
      <div className="pt-3 mt-3 border-t border-slate-100 space-y-3">
        <div className="flex items-baseline justify-between">
          <div>
            <span className="text-[10px] uppercase font-bold text-slate-400 block font-mono">Best Price</span>
            {minPrice !== undefined && minPrice > 0 ? (
              <span className="text-base sm:text-lg font-black text-slate-900 leading-tight">
                {currencySymbol}{Number(minPrice).toFixed(2)}
              </span>
            ) : (
              <span className="text-xs text-slate-500 italic">Compare stores</span>
            )}
          </div>

          {retailerName && (
            <div className="text-right">
              <span className="text-[10px] text-slate-400 block font-mono">at</span>
              <span className="text-xs font-semibold text-slate-700 truncate max-w-[100px] block">
                {retailerName}
              </span>
            </div>
          )}
        </div>

        {/* Action Buttons */}
        <div className="grid grid-cols-2 gap-2 pt-1">
          <Link
            href={productPath(market, product.slug)}
            className="py-2 px-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-[11px] font-bold text-center flex items-center justify-center gap-1 transition-colors"
          >
            <SlidersHorizontal className="w-3 h-3" />
            <span>Compare</span>
          </Link>

          {bestOfferId ? (
            <a
              href={getOutboundUrl(bestOfferId)}
              target="_blank"
              rel="nofollow sponsored noopener"
              onClick={handleDirectBuy}
              className="py-2 px-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold text-center flex items-center justify-center gap-1 shadow-xs transition-colors"
            >
              <span>Buy Now</span>
              <ExternalLink className="w-3 h-3" />
            </a>
          ) : (
            <Link
              href={productPath(market, product.slug)}
              className="py-2 px-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold text-center flex items-center justify-center gap-1 shadow-xs transition-colors"
            >
              <span>View Deal</span>
              <ArrowRight className="w-3 h-3" />
            </Link>
          )}
        </div>
      </div>
    </div>
  );
};
