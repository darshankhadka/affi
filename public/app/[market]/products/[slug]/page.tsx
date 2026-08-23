import React from 'react';
import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { StructuredData } from '@/components/seo/StructuredData';
import { OfferComparisonTable } from '@/components/product/OfferComparisonTable';
import { Tag, ShieldCheck, CheckCircle2, ChevronRight, Laptop, Layers } from 'lucide-react';
import Link from 'next/link';

interface ProductPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateMetadata({ params }: ProductPageProps): Promise<Metadata> {
  const { market, slug } = await params;

  try {
    const res = await fetchApi(`/products/${slug}?market=${market}`);
    const product = res.data;
    const seo = res.meta?.seo;

    if (!product) return {};

    return {
      title: seo?.title || `${product.name} Best Price & Deals | ARIKARTECH`,
      description: seo?.description || product.short_description || `Compare prices for ${product.name} across verified tech retailers.`,
      alternates: {
        canonical: seo?.canonical || `https://arikartech.com/${market}/products/${slug}`,
        languages: seo?.hreflang || {},
      },
      openGraph: {
        title: seo?.open_graph?.title || product.name,
        description: seo?.open_graph?.description || product.short_description,
        url: seo?.open_graph?.url,
        siteName: 'ARIKARTECH',
        images: product.primary_image?.url ? [{ url: product.primary_image.url }] : [],
      },
    };
  } catch {
    return {
      title: 'Product Not Found | ARIKARTECH',
    };
  }
}

export default async function ProductPage({ params }: ProductPageProps) {
  const { market, slug } = await params;

  let product: any = null;
  let structuredData: any = null;

  try {
    const res = await fetchApi(`/products/${slug}?market=${market}`);
    product = res.data;
    structuredData = res.meta?.structured_data;
  } catch {
    notFound();
  }

  if (!product) {
    notFound();
  }

  const bestPrice = product.best_price;
  const offers = product.offers || [];
  const currencySymbol = bestPrice?.currency?.symbol || '$';

  return (
    <div className="space-y-12">
      {structuredData && <StructuredData data={structuredData} />}

      {/* Breadcrumb Bar */}
      <nav className="flex items-center gap-2 text-xs text-slate-400">
        <Link href={`/${market}`} className="hover:text-emerald-400">Home</Link>
        <ChevronRight className="w-3 h-3 text-slate-600" />
        {product.category && (
          <>
            <Link href={`/${market}/categories/${product.category.slug}`} className="hover:text-emerald-400">
              {product.category.name}
            </Link>
            <ChevronRight className="w-3 h-3 text-slate-600" />
          </>
        )}
        <span className="text-slate-200 truncate max-w-xs">{product.name}</span>
      </nav>

      {/* Top Hero: Product Overview & Best Price Highlights */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Left: Product Images */}
        <div className="lg:col-span-5 glass-card rounded-3xl p-8 flex items-center justify-center border border-slate-800 relative aspect-[4/3] bg-slate-900/60">
          {product.primary_image?.url ? (
            <img
              src={product.primary_image.url}
              alt={product.primary_image.alt_text || product.name}
              className="object-contain w-full h-full p-4"
            />
          ) : (
            <div className="flex flex-col items-center justify-center text-slate-500 gap-3">
              <Laptop className="w-16 h-16 text-slate-600" />
              <span className="text-xs font-mono uppercase tracking-widest text-slate-500">
                {product.brand?.name || 'Verified Tech'}
              </span>
            </div>
          )}
        </div>

        {/* Right: Key Specs & Price Summary */}
        <div className="lg:col-span-7 space-y-6">
          <div>
            <div className="flex items-center gap-3 mb-2">
              {product.brand && (
                <span className="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-mono text-xs font-bold uppercase">
                  {product.brand.name}
                </span>
              )}
              {product.model_number && (
                <span className="text-xs text-slate-400 font-mono">
                  Model: {product.model_number}
                </span>
              )}
            </div>

            <h1 className="text-2xl sm:text-4xl font-extrabold text-slate-100 tracking-tight leading-tight">
              {product.name}
            </h1>
          </div>

          {/* Best Price Banner Card */}
          <div className="glass-card rounded-2xl p-6 border-emerald-500/30 bg-emerald-950/10 space-y-3">
            <div className="flex items-baseline justify-between">
              <div>
                <span className="text-xs uppercase font-bold text-slate-400 block tracking-wider">
                  Lowest Verified Price
                </span>
                {bestPrice && bestPrice.min_price > 0 ? (
                  <span className="text-3xl sm:text-4xl font-black text-emerald-400">
                    {currencySymbol}{Number(bestPrice.min_price).toFixed(2)}
                  </span>
                ) : (
                  <span className="text-xl font-bold text-slate-300">Compare Stores Below</span>
                )}
              </div>

              {offers.length > 0 && (
                <span className="px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold text-slate-300">
                  {offers.length} {offers.length === 1 ? 'Store Offer' : 'Store Offers'}
                </span>
              )}
            </div>

            <p className="text-xs text-slate-400">
              Prices compared across authorized retailers in {market.toUpperCase()}. Updated continuously with automated rate-limited checks.
            </p>
          </div>

          {/* Description */}
          {product.description && (
            <div className="prose prose-invert prose-xs text-slate-300 leading-relaxed max-w-none">
              <p>{product.description}</p>
            </div>
          )}
        </div>
      </div>

      {/* Main Section: Retailer Price Comparison Engine */}
      <section className="space-y-6 pt-6">
        <div className="flex items-center justify-between border-b border-slate-800 pb-4">
          <div>
            <h2 className="text-xl font-bold text-slate-100 flex items-center gap-2">
              <Tag className="w-5 h-5 text-emerald-400" />
              Compare Retailer Offers & Prices
            </h2>
            <p className="text-xs text-slate-400 mt-1">
              Select an authorized seller to view current in-stock availability and deals.
            </p>
          </div>
        </div>

        <OfferComparisonTable offers={offers} productName={product.name} />
      </section>

      {/* Hardware Specifications Grid */}
      {product.specifications && product.specifications.length > 0 && (
        <section className="space-y-6 pt-6">
          <div className="border-b border-slate-800 pb-4">
            <h2 className="text-xl font-bold text-slate-100 flex items-center gap-2">
              <Layers className="w-5 h-5 text-emerald-400" />
              Technical Specifications
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {product.specifications.map((group: any, idx: number) => (
              <div key={idx} className="glass-card rounded-2xl p-6 space-y-4">
                <h3 className="text-sm font-bold text-emerald-400 uppercase tracking-wider font-mono">
                  {group.group}
                </h3>
                <dl className="divide-y divide-slate-800/60 text-xs">
                  {group.items.map((item: any, sIdx: number) => (
                    <div key={sIdx} className="py-2.5 flex justify-between gap-4">
                      <dt className="text-slate-400">{item.name}</dt>
                      <dd className="font-semibold text-slate-200 text-right">{item.value}</dd>
                    </div>
                  ))}
                </dl>
              </div>
            ))}
          </div>
        </section>
      )}
    </div>
  );
}
