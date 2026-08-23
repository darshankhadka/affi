import React from 'react';
import { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { Layers } from 'lucide-react';

export const dynamicParams = false;

interface CategoryPageProps {
  params: Promise<{
    market: string;
    slug: string;
  }>;
}

export async function generateStaticParams() {
  const markets = ['us', 'uk', 'de', 'fr', 'es', 'it', 'nl', 'au', 'nz'];
  const defaultCategories = [
    'laptops',
    'smartphones',
    'tablets',
    'monitors',
    'cpus',
    'gpus',
    'motherboards',
    'ram',
    'ssds',
    'power-supplies',
    'pc-cases',
    'routers',
    'network-switches',
    'cables-adapters',
    'keyboards',
    'mice',
    'headphones',
    'tvs',
    'smartwatches',
    'audio',
  ];

  const params: { market: string; slug: string }[] = [];

  try {
    const res = await fetchApi('/categories').catch(() => null);
    const categories = res?.data || [];
    const slugs = categories.length > 0 ? categories.map((c: any) => c.slug) : defaultCategories;

    for (const m of markets) {
      for (const slug of slugs) {
        if (slug) {
          params.push({ market: m, slug });
        }
      }
    }
  } catch {
    for (const m of markets) {
      for (const slug of defaultCategories) {
        params.push({ market: m, slug });
      }
    }
  }

  return params;
}

export async function generateMetadata({ params }: CategoryPageProps): Promise<Metadata> {
  const { market, slug } = await params;

  try {
    const res = await fetchApi(`/categories/${slug}`);
    const cat = res.data;
    if (!cat) return {};

    return {
      title: `Best ${cat.name} Deals & Price Comparison (${market.toUpperCase()}) | ARIKARTECH`,
      description: `Compare prices and discover top-rated ${cat.name} from authorized retailers in ${market.toUpperCase()} on ARIKARTECH.`,
    };
  } catch {
    return { title: 'Category | ARIKARTECH' };
  }
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { market, slug } = await params;

  let category: any = null;
  let products: any[] = [];

  try {
    const [catRes, prodRes] = await Promise.all([
      fetchApi(`/categories/${slug}`).catch(() => null),
      fetchApi(`/products?market=${market}&category=${slug}&per_page=24`).catch(() => null),
    ]);

    category = catRes?.data;
    products = prodRes?.data || [];
  } catch {
    // If not found in API, provide clean fallback structure
    category = { name: slug.replace(/-/g, ' ').toUpperCase(), slug };
  }

  if (!category) {
    category = { name: slug.replace(/-/g, ' ').toUpperCase(), slug };
  }

  return (
    <div className="space-y-8">
      {/* Category Header */}
      <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm space-y-3">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-mono font-semibold">
          <Layers className="w-3.5 h-3.5" />
          <span>Category Discovery</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
          {category.name} Price Comparison
        </h1>
        {category.description && (
          <p className="text-xs text-slate-500 max-w-2xl">{category.description}</p>
        )}
      </div>

      {/* Products Grid */}
      {products.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} market={market} />
          ))}
        </div>
      ) : (
        <EmptyState
          title={`No ${category.name} currently indexed in ${market.toUpperCase()}.`}
          description="New products and live retailer feeds are constantly processed by our bounded ingestion engine."
        />
      )}
    </div>
  );
}
