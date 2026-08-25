'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { fetchApi, getOutboundUrl } from '@/lib/api';
import { StructuredData } from '@/components/seo/StructuredData';
import { OfferComparisonTable, RetailerOffer } from '@/components/product/OfferComparisonTable';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { trackAffiliateClick } from '@/components/seo/GoogleAnalytics';
import {
  Tag,
  ShieldCheck,
  Laptop,
  Layers,
  ExternalLink,
  CheckCircle2,
  XCircle,
  Clock,
  Sparkles,
  ArrowRight,
  Info,
  Barcode
} from 'lucide-react';

interface ProductDetailClientProps {
  market: string;
  slug: string;
}

export const ProductDetailClient: React.FC<ProductDetailClientProps> = ({ market, slug }) => {
  const [product, setProduct] = useState<any>(null);
  const [structuredData, setStructuredData] = useState<any>(null);
  const [relatedProducts, setRelatedProducts] = useState<any[]>([]);
  const [activeImage, setActiveImage] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  const loadProduct = () => {
    setLoading(true);
    setError(false);

    fetchApi(`/products/${slug}?market=${market}`)
      .then((res) => {
        const prod = res.data;
        setProduct(prod);
        setStructuredData(res.meta?.structured_data);
        setActiveImage(prod.primary_image?.url || null);
        setLoading(false);

        // Fetch related products in the same category
        if (prod.category?.slug) {
          fetchApi(`/products?category=${prod.category.slug}&market=${market}&per_page=4`)
            .then((catRes) => {
              const items = (catRes.data || []).filter((p: any) => p.slug !== slug).slice(0, 4);
              setRelatedProducts(items);
            })
            .catch(() => setRelatedProducts([]));
        }
      })
      .catch(() => {
        setError(true);
        setProduct(null);
        setLoading(false);
      });
  };

  useEffect(() => {
    loadProduct();
  }, [market, slug]);

  if (loading) {
    return (
      <div className="space-y-10 animate-pulse">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          <div className="lg:col-span-5 bg-white rounded-3xl p-8 h-96 border border-slate-200" />
          <div className="lg:col-span-7 space-y-4">
            <div className="h-6 w-32 bg-slate-200 rounded-full" />
            <div className="h-10 w-3/4 bg-slate-200 rounded-xl" />
            <div className="h-40 bg-emerald-50 rounded-2xl border border-emerald-100" />
          </div>
        </div>
        <div className="h-64 bg-white rounded-2xl border border-slate-200" />
      </div>
    );
  }

  if (error || !product) {
    return (
      <div className="space-y-6">
        <EmptyState
          title="Product Currently Unavailable in this Market"
          description={`Verified store offers for this item are not currently active in ${market.toUpperCase()}. Explore other verified tech hardware and live deals below.`}
          actionHref={`/${market}`}
          actionText="Back to Hardware Catalog"
        />
      </div>
    );
  }

  const bestPrice = product.best_price;
  const offers: RetailerOffer[] = product.offers || [];
  const currencySymbol = bestPrice?.currency?.symbol || '$';
  const topOffer = offers.find((o) => o.availability === 'in_stock') || offers[0];
  const images = product.images && product.images.length > 0 ? product.images : (product.primary_image ? [product.primary_image] : []);

  const handleTopOfferClick = () => {
    if (topOffer) {
      trackAffiliateClick(
        topOffer.id,
        product.name,
        topOffer.retailer?.name || 'Retailer',
        topOffer.price,
        topOffer.currency?.code || 'USD'
      );
    }
  };

  return (
    <div className="space-y-12 pb-16">
      {structuredData && <StructuredData data={structuredData} />}

      {/* Above the Fold: Product Hero, Key Specs & Immediate Purchase CTA */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Left: Product Images & Gallery */}
        <div className="lg:col-span-5 space-y-4">
          <div className="bg-white rounded-3xl p-6 sm:p-8 flex items-center justify-center border border-slate-200 shadow-sm relative aspect-[4/3] overflow-hidden group">
            {activeImage ? (
              <img
                src={activeImage}
                alt={product.primary_image?.alt_text || product.name}
                className="object-contain w-full h-full p-2 transition-transform duration-300 group-hover:scale-105"
              />
            ) : (
              <div className="flex flex-col items-center justify-center text-slate-400 gap-3">
                <Laptop className="w-16 h-16 text-slate-300" />
                <span className="text-xs font-mono uppercase tracking-widest text-slate-400">
                  {product.brand?.name || 'Verified Product'}
                </span>
              </div>
            )}

            {product.category && (
              <span className="absolute top-4 left-4 px-3 py-1 rounded-full bg-slate-900/80 backdrop-blur-sm text-white font-mono text-[10px] font-semibold tracking-wider uppercase">
                {product.category.name}
              </span>
            )}
          </div>

          {/* Thumbnail Gallery */}
          {images.length > 1 && (
            <div className="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-thin">
              {images.map((img: any, idx: number) => (
                <button
                  key={idx}
                  type="button"
                  onClick={() => setActiveImage(img.url)}
                  className={`w-16 h-16 rounded-xl bg-white border p-1 shrink-0 transition-all ${
                    activeImage === img.url
                      ? 'border-emerald-600 ring-2 ring-emerald-500/20'
                      : 'border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <img
                    src={img.url}
                    alt={img.alt_text || `Thumbnail ${idx + 1}`}
                    className="w-full h-full object-contain"
                  />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Right: Key Specs, Pricing Card & Primary CTA */}
        <div className="lg:col-span-7 space-y-6">
          <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-2">
              {product.brand && (
                <Link
                  href={`/${market}/brands/${product.brand.slug}`}
                  className="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-xs font-bold uppercase hover:bg-emerald-100 transition-colors"
                >
                  {product.brand.name}
                </Link>
              )}
              {product.model_number && (
                <span className="text-xs text-slate-500 font-mono bg-slate-100 px-2.5 py-0.5 rounded-md">
                  Model: {product.model_number}
                </span>
              )}
            </div>

            <h1 className="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
              {product.name}
            </h1>

            {product.short_description && (
              <p className="text-sm text-slate-600 leading-relaxed pt-1">
                {product.short_description}
              </p>
            )}
          </div>

          {/* Primary Purchase Card (Above the Fold) */}
          <div className="bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/50 rounded-2xl p-6 border-2 border-emerald-500/40 shadow-sm space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-baseline justify-between gap-2">
              <div>
                <span className="text-[11px] uppercase font-bold text-emerald-800 tracking-wider flex items-center gap-1.5 font-mono">
                  <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                  Best Verified Price We Found
                </span>
                {bestPrice && bestPrice.min_price > 0 ? (
                  <div className="flex items-baseline gap-2 mt-1">
                    <span className="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                      {currencySymbol}{Number(bestPrice.min_price).toFixed(2)}
                    </span>
                    {topOffer?.original_price && topOffer.original_price > bestPrice.min_price && (
                      <span className="text-sm text-slate-400 line-through">
                        {currencySymbol}{Number(topOffer.original_price).toFixed(2)}
                      </span>
                    )}
                  </div>
                ) : (
                  <span className="text-xl font-bold text-slate-700 mt-1 block">Compare Stores Below</span>
                )}
              </div>

              {topOffer?.retailer && (
                <div className="text-left sm:text-right">
                  <span className="text-xs text-slate-500 block">Sold by</span>
                  <span className="text-sm font-bold text-slate-800">{topOffer.retailer.name}</span>
                </div>
              )}
            </div>

            {/* Primary Action Buttons */}
            {topOffer ? (
              <div className="flex flex-col sm:flex-row gap-3 pt-2">
                <a
                  href={getOutboundUrl(topOffer.id)}
                  target="_blank"
                  rel="nofollow sponsored noopener"
                  onClick={handleTopOfferClick}
                  className="flex-1 py-4 px-6 rounded-xl font-bold text-sm sm:text-base uppercase tracking-wider bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20 hover:shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all hover:scale-[1.01] active:scale-[0.99]"
                >
                  <span>BUY NOW ({currencySymbol}{Number(topOffer.price).toFixed(2)})</span>
                  <ExternalLink className="w-4 h-4" />
                </a>

                {offers.length > 1 && (
                  <a
                    href="#offers"
                    className="py-4 px-6 rounded-xl font-bold text-xs uppercase tracking-wider bg-slate-900 hover:bg-slate-800 text-white flex items-center justify-center gap-2 transition-colors shrink-0"
                  >
                    <span>COMPARE {offers.length} OFFERS</span>
                  </a>
                )}
              </div>
            ) : null}

            {/* Micro Trust Indicators */}
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 text-[11px] text-slate-600 border-t border-emerald-100/60 font-mono">
              <div className="flex items-center gap-1.5">
                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>Verified Retailer</span>
              </div>
              <div className="flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>Live Feed Sync</span>
              </div>
              <div className="flex items-center gap-1.5 col-span-2 sm:col-span-1">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                <span>Direct Official Store</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Section: Retailer Price Comparison Table */}
      <section id="offers" className="space-y-4 pt-4">
        <div className="flex items-center justify-between border-b border-slate-200 pb-4">
          <div>
            <h2 className="text-xl sm:text-2xl font-bold text-slate-900 flex items-center gap-2">
              <Tag className="w-5 h-5 text-emerald-600" />
              Compare All Retailer Offers
            </h2>
            <p className="text-xs text-slate-500 mt-1">
              Live price comparison across verified merchant feeds in {market.toUpperCase()}.
            </p>
          </div>
        </div>

        <OfferComparisonTable offers={offers} productName={product.name} />
      </section>

      {/* Full Description & Product Details */}
      <section className="space-y-4 pt-4 border-t border-slate-200">
        <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
          <Info className="w-5 h-5 text-emerald-600" />
          Product Description & Overview
        </h2>

        <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm leading-relaxed text-sm text-slate-700">
          {product.description ? (
            <div className="prose prose-slate max-w-none space-y-3">
              <p>{product.description}</p>
            </div>
          ) : (
            <p className="text-slate-500 italic">
              Detailed technical summary verified from merchant catalog feed. See technical specifications below.
            </p>
          )}
        </div>
      </section>

      {/* Product Identifiers Barcode Grid (if present) */}
      {(product.canonical_ean || product.canonical_upc || product.canonical_mpn || product.model_number) && (
        <section className="space-y-4 pt-4 border-t border-slate-200">
          <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
            <Barcode className="w-5 h-5 text-emerald-600" />
            Product Identifiers & GTIN
          </h2>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {product.canonical_ean && (
              <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-xs">
                <span className="text-[10px] uppercase font-mono text-slate-400 block font-bold">EAN / GTIN-13</span>
                <span className="text-xs font-mono font-bold text-slate-800">{product.canonical_ean}</span>
              </div>
            )}
            {product.canonical_upc && (
              <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-xs">
                <span className="text-[10px] uppercase font-mono text-slate-400 block font-bold">UPC / GTIN-12</span>
                <span className="text-xs font-mono font-bold text-slate-800">{product.canonical_upc}</span>
              </div>
            )}
            {product.canonical_mpn && (
              <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-xs">
                <span className="text-[10px] uppercase font-mono text-slate-400 block font-bold">Manufacturer MPN</span>
                <span className="text-xs font-mono font-bold text-slate-800">{product.canonical_mpn}</span>
              </div>
            )}
            {product.model_number && (
              <div className="p-4 bg-white rounded-xl border border-slate-200 shadow-xs">
                <span className="text-[10px] uppercase font-mono text-slate-400 block font-bold">Model Number</span>
                <span className="text-xs font-mono font-bold text-slate-800">{product.model_number}</span>
              </div>
            )}
          </div>
        </section>
      )}

      {/* Hardware Specifications Grid */}
      {product.specifications && product.specifications.length > 0 && (
        <section className="space-y-4 pt-4 border-t border-slate-200">
          <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
            <Layers className="w-5 h-5 text-emerald-600" />
            Technical Specifications
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {product.specifications.map((group: any, idx: number) => (
              <div key={idx} className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h3 className="text-sm font-bold text-emerald-700 uppercase tracking-wider font-mono">
                  {group.group}
                </h3>
                <dl className="divide-y divide-slate-100 text-xs">
                  {group.items.map((item: any, sIdx: number) => (
                    <div key={sIdx} className="py-2.5 flex justify-between gap-4">
                      <dt className="text-slate-500">{item.name}</dt>
                      <dd className="font-semibold text-slate-800 text-right">{item.value}</dd>
                    </div>
                  ))}
                </dl>
              </div>
            ))}
          </div>
        </section>
      )}

      {/* Real Price History Section */}
      {product.price_history && product.price_history.length > 0 && (
        <section className="space-y-4 pt-4 border-t border-slate-200">
          <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
            <ShieldCheck className="w-5 h-5 text-emerald-600" />
            Verified Price History & Volatility
          </h2>

          <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm overflow-x-auto">
            <table className="w-full text-xs text-left">
              <thead>
                <tr className="border-b border-slate-200 text-slate-500 font-mono">
                  <th className="pb-3">Recorded Date</th>
                  <th className="pb-3">Price</th>
                  <th className="pb-3">Availability</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 font-mono">
                {product.price_history.slice(-10).map((ph: any, pIdx: number) => (
                  <tr key={pIdx}>
                    <td className="py-2.5 text-slate-600">{new Date(ph.recorded_at).toLocaleDateString()}</td>
                    <td className="py-2.5 text-emerald-700 font-bold">{currencySymbol}{Number(ph.price).toFixed(2)}</td>
                    <td className="py-2.5 text-slate-700 capitalize">{ph.availability}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Related Products in Same Category */}
      {relatedProducts.length > 0 && (
        <section className="space-y-6 pt-4 border-t border-slate-200">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-bold text-slate-900">Similar Products in {product.category?.name || 'Category'}</h2>
              <p className="text-xs text-slate-500 mt-1">Explore alternative hardware models and verified deals</p>
            </div>
            {product.category && (
              <Link
                href={`/${market}/categories/${product.category.slug}`}
                className="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
              >
                View all <ArrowRight className="w-3.5 h-3.5" />
              </Link>
            )}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {relatedProducts.map((rel) => (
              <ProductCard key={rel.id} product={rel} market={market} />
            ))}
          </div>
        </section>
      )}

      {/* Mobile Sticky Bottom Purchase Bar */}
      {topOffer && (
        <div className="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 p-3 sm:hidden shadow-2xl flex items-center justify-between gap-4">
          <div className="min-w-0">
            <span className="text-[11px] font-medium text-slate-500 block truncate">{product.name}</span>
            <span className="text-lg font-black text-slate-900">
              {currencySymbol}{Number(topOffer.price).toFixed(2)}
            </span>
          </div>

          <a
            href={getOutboundUrl(topOffer.id)}
            target="_blank"
            rel="nofollow sponsored noopener"
            onClick={handleTopOfferClick}
            className="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 shrink-0 shadow-md shadow-emerald-600/30"
          >
            <span>BUY NOW</span>
            <ExternalLink className="w-3.5 h-3.5" />
          </a>
        </div>
      )}
    </div>
  );
};
