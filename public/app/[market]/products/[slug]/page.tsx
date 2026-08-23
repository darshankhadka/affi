import React from 'react';
import { Metadata } from 'next';
import { fetchApi } from '@/lib/api';
import { StructuredData } from '@/components/seo/StructuredData';
import { OfferComparisonTable } from '@/components/product/OfferComparisonTable';
import { EmptyState } from '@/components/ui/EmptyState';
import { Tag, ShieldCheck, ChevronRight, Laptop, Layers } from 'lucide-react';
import Link from 'next/link';

export const dynamicParams = false;

interface ProductPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateStaticParams() {
  const markets = ['us', 'uk', 'de', 'fr', 'es', 'it', 'nl', 'au', 'nz'];
  const defaultSlugs = ['catalog'];
  const params: { market: string; slug: string }[] = [];

  try {
    const res = await fetchApi('/products?per_page=100').catch(() => null);
    const products = res?.data || [];
    const slugs = products.length > 0 ? products.map((p: any) => p.slug) : defaultSlugs;

    for (const m of markets) {
      for (const slug of slugs) {
        if (slug) {
          params.push({ market: m, slug });
        }
      }
    }
  } catch {
    for (const m of markets) {
      for (const slug of defaultSlugs) {
        params.push({ market: m, slug });
      }
    }
  }

  return params;
}

export async function generateMetadata({ params }: ProductPageProps): Promise<Metadata> {
  const { market, slug } = await params;

  try {
    const res = await fetchApi(`/products/${slug}?market=${market}`);
    const product = res.data;
    const seo = res.meta?.seo;

    if (!product) {
      return {
        title: 'Technology Product Deals & Price Comparison | ARIKARTECH',
        description: 'Compare prices across verified hardware retailers.',
      };
    }

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
      title: 'Technology Product Deals & Price Comparison | ARIKARTECH',
      description: 'Compare prices across verified hardware retailers.',
    };
  }
}

export default async function ProductPage({ params }: ProductPageProps) {
  const { market, slug } = await params;

  let product: any = null;
  let structuredData: any = null;

  try {
    const res = await fetchApi(`/products/${slug}?market=${market}`).catch(() => null);
    product = res?.data;
    structuredData = res?.meta?.structured_data;
  } catch {
    product = null;
  }

  if (!product) {
    return (
      <div className="space-y-8">
        <nav className="flex items-center gap-2 text-xs text-slate-500">
          <Link href={`/${market}`} className="hover:text-emerald-600">Home</Link>
          <ChevronRight className="w-3 h-3 text-slate-400" />
          <span className="text-slate-800 font-medium truncate">Hardware Catalog</span>
        </nav>

        <EmptyState
          title="Product listing is updating."
          description={`Verified store offers and specifications for this hardware model are currently refreshing in the ${market.toUpperCase()} market.`}
          actionHref={`/${market}`}
          actionText="Browse Technology Catalog"
        />
      </div>
    );
  }

  const bestPrice = product.best_price;
  const offers = product.offers || [];
  const currencySymbol = bestPrice?.currency?.symbol || '$';

  return (
    <div className="space-y-10">
      {structuredData && <StructuredData data={structuredData} />}

      {/* Breadcrumb Bar */}
      <nav className="flex items-center gap-2 text-xs text-slate-500">
        <Link href={`/${market}`} className="hover:text-emerald-600">Home</Link>
        <ChevronRight className="w-3 h-3 text-slate-400" />
        {product.category && (
          <>
            <Link href={`/${market}/categories/${product.category.slug}`} className="hover:text-emerald-600">
              {product.category.name}
            </Link>
            <ChevronRight className="w-3 h-3 text-slate-400" />
          </>
        )}
        <span className="text-slate-800 font-medium truncate max-w-xs">{product.name}</span>
      </nav>

      {/* Top Hero: Product Overview & Best Price Highlights */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Left: Product Images */}
        <div className="lg:col-span-5 bg-white rounded-3xl p-8 flex items-center justify-center border border-slate-200 shadow-sm relative aspect-[4/3]">
          {product.primary_image?.url ? (
            <img
              src={product.primary_image.url}
              alt={product.primary_image.alt_text || product.name}
              className="object-contain w-full h-full p-4"
            />
          ) : (
            <div className="flex flex-col items-center justify-center text-slate-400 gap-3">
              <Laptop className="w-16 h-16 text-slate-300" />
              <span className="text-xs font-mono uppercase tracking-widest text-slate-400">
                {product.brand?.name || 'Verified Tech'}
              </span>
            </div>
          )}
        </div>

        {/* Right: Key Specs & Price Summary */}
        <div className="lg:col-span-7 space-y-5">
          <div>
            <div className="flex items-center gap-3 mb-2">
              {product.brand && (
                <span className="px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-xs font-bold uppercase">
                  {product.brand.name}
                </span>
              )}
              {product.model_number && (
                <span className="text-xs text-slate-500 font-mono">
                  Model: {product.model_number}
                </span>
              )}
            </div>

            <h1 className="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
              {product.name}
            </h1>
          </div>

          {/* Best Price Banner Card */}
          <div className="bg-emerald-50/50 rounded-2xl p-6 border border-emerald-200 space-y-3 shadow-sm">
            <div className="flex items-baseline justify-between">
              <div>
                <span className="text-xs uppercase font-bold text-slate-600 block tracking-wider">
                  Best Price We Found
                </span>
                {bestPrice && bestPrice.min_price > 0 ? (
                  <span className="text-3xl sm:text-4xl font-black text-emerald-700">
                    {currencySymbol}{Number(bestPrice.min_price).toFixed(2)}
                  </span>
                ) : (
                  <span className="text-xl font-bold text-slate-700">Compare Stores Below</span>
                )}
              </div>

              {offers.length > 0 && (
                <span className="px-3 py-1 rounded-full bg-white border border-slate-200 text-xs font-semibold text-slate-700 shadow-xs">
                  {offers.length} {offers.length === 1 ? 'Store Offer' : 'Store Offers'}
                </span>
              )}
            </div>

            <p className="text-xs text-slate-600">
              Prices compared across verified retailers in {market.toUpperCase()}. Continuously refreshed with rate-limited checks.
            </p>
          </div>

          {/* Description */}
          {product.description && (
            <div className="prose prose-xs text-slate-600 leading-relaxed max-w-none">
              <p>{product.description}</p>
            </div>
          )}
        </div>
      </div>

      {/* Main Section: Retailer Price Comparison Engine */}
      <section className="space-y-5 pt-4">
        <div className="flex items-center justify-between border-b border-slate-200 pb-4">
          <div>
            <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
              <Tag className="w-5 h-5 text-emerald-600" />
              Compare Retailer Offers & Prices
            </h2>
            <p className="text-xs text-slate-500 mt-1">
              Select a verified seller to view current in-stock availability and deals.
            </p>
          </div>
        </div>

        <OfferComparisonTable offers={offers} productName={product.name} />
      </section>

      {/* Hardware Specifications Grid */}
      {product.specifications && product.specifications.length > 0 && (
        <section className="space-y-5 pt-4">
          <div className="border-b border-slate-200 pb-4">
            <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
              <Layers className="w-5 h-5 text-emerald-600" />
              Technical Specifications
            </h2>
          </div>

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
        <section className="space-y-5 pt-4">
          <div className="border-b border-slate-200 pb-4">
            <h2 className="text-xl font-bold text-slate-900 flex items-center gap-2">
              <ShieldCheck className="w-5 h-5 text-emerald-600" />
              Verified Price History & Volatility
            </h2>
          </div>

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
    </div>
  );
}
