import React from 'react';
import Link from 'next/link';
import { ArrowRight, Laptop } from 'lucide-react';
import { productPath } from '@/lib/url';

interface ProductCardProps {
  product: {
    id: number;
    name: string;
    slug: string;
    model_number?: string | null;
    short_description?: string | null;
    primary_image?: { url: string; alt_text?: string } | null;
    brand?: { name: string } | null;
    category?: { name: string } | null;
    best_price?: {
      min_price: number;
      offer_count: number;
      currency?: { symbol: string };
    } | null;
  };
  market: string;
}

export const ProductCard: React.FC<ProductCardProps> = ({ product, market }) => {
  const currencySymbol = product.best_price?.currency?.symbol || '$';
  const minPrice = product.best_price?.min_price;
  const offerCount = product.best_price?.offer_count || 0;

  return (
    <Link
      href={productPath(market, product.slug)}
      className="group bg-white rounded-2xl p-5 flex flex-col justify-between border border-slate-200 shadow-xs hover:shadow-md hover:border-slate-300 transition-all duration-200 relative overflow-hidden"
    >
      <div>
        {/* Image / Fallback Container */}
        <div className="aspect-[4/3] rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-4 overflow-hidden relative">
          {product.primary_image?.url ? (
            <img
              src={product.primary_image.url}
              alt={product.primary_image.alt_text || product.name}
              className="object-contain w-full h-full p-4 group-hover:scale-105 transition-transform duration-300"
            />
          ) : (
            <div className="flex flex-col items-center justify-center text-slate-400 gap-2">
              <Laptop className="w-10 h-10 text-slate-300" />
              <span className="text-[10px] font-mono uppercase tracking-wider">{product.brand?.name || 'Tech'}</span>
            </div>
          )}
        </div>

        {/* Brand & Category tags */}
        <div className="flex items-center gap-2 mb-2">
          {product.brand && (
            <span className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 font-mono">
              {product.brand.name}
            </span>
          )}
          {product.category && (
            <span className="text-[11px] text-slate-500">· {product.category.name}</span>
          )}
        </div>

        {/* Product Title */}
        <h3 className="font-bold text-sm text-slate-900 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-snug">
          {product.name}
        </h3>

        {/* Short Description */}
        {product.short_description && (
          <p className="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
            {product.short_description}
          </p>
        )}
      </div>

      {/* Pricing & Deals Footer */}
      <div className="pt-4 mt-4 border-t border-slate-100 flex items-end justify-between">
        <div>
          {minPrice !== undefined && minPrice > 0 ? (
            <div>
              <span className="text-[10px] uppercase font-semibold text-slate-500 block">Best Price We Found</span>
              <span className="text-lg font-extrabold text-emerald-700 leading-tight">
                {currencySymbol}{Number(minPrice).toFixed(2)}
              </span>
            </div>
          ) : (
            <span className="text-xs text-slate-500 italic">Check store prices</span>
          )}

          {offerCount > 0 && (
            <p className="text-[11px] text-slate-500 mt-0.5">
              {offerCount} {offerCount === 1 ? 'store offer' : 'store offers'}
            </p>
          )}
        </div>

        <div className="p-2 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 group-hover:text-emerald-700 group-hover:bg-emerald-50 group-hover:border-emerald-200 transition-colors">
          <ArrowRight className="w-4 h-4" />
        </div>
      </div>
    </Link>
  );
};
