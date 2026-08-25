'use client';

import React, { useEffect, useState, useMemo } from 'react';
import { fetchApi } from '@/lib/api';
import { ProductCard } from '@/components/product/ProductCard';
import { EmptyState } from '@/components/ui/EmptyState';
import { getMarketDef } from '@/lib/catalog';
import { Search, SlidersHorizontal, ArrowUpDown } from 'lucide-react';

interface CategoryProductsClientProps {
  market: string;
  categorySlug: string;
  categoryName: string;
}

export const CategoryProductsClient: React.FC<CategoryProductsClientProps> = ({
  market,
  categorySlug,
  categoryName,
}) => {
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedBrand, setSelectedBrand] = useState<string>('all');
  const [sortBy, setSortBy] = useState<string>('best_price');

  const marketInfo = getMarketDef(market);

  const loadProducts = () => {
    setLoading(true);

    fetchApi(`/products?market=${market}&category=${categorySlug}&per_page=50`)
      .then((res) => {
        setProducts(res.data || []);
        setLoading(false);
      })
      .catch(() => {
        setProducts([]);
        setLoading(false);
      });
  };

  useEffect(() => {
    loadProducts();
  }, [market, categorySlug]);

  // Extract unique brands for filtering
  const availableBrands = useMemo(() => {
    const brandsMap = new Map<string, string>();
    products.forEach((p) => {
      if (p.brand?.name && p.brand?.slug) {
        brandsMap.set(p.brand.slug, p.brand.name);
      }
    });
    return Array.from(brandsMap.entries()).map(([slug, name]) => ({ slug, name }));
  }, [products]);

  // Filtered & Sorted products
  const filteredProducts = useMemo(() => {
    let result = [...products];

    // Search query filter
    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      result = result.filter(
        (p) =>
          p.name.toLowerCase().includes(q) ||
          p.brand?.name?.toLowerCase().includes(q) ||
          p.model_number?.toLowerCase().includes(q)
      );
    }

    // Brand filter
    if (selectedBrand !== 'all') {
      result = result.filter((p) => p.brand?.slug === selectedBrand);
    }

    // Sorting
    result.sort((a, b) => {
      const priceA = a.best_price?.min_price ?? 999999;
      const priceB = b.best_price?.min_price ?? 999999;

      if (sortBy === 'best_price') return priceA - priceB;
      if (sortBy === 'price_desc') return priceB - priceA;
      if (sortBy === 'name') return a.name.localeCompare(b.name);
      return 0; // Default order
    });

    return result;
  }, [products, searchQuery, selectedBrand, sortBy]);

  return (
    <div className="space-y-6">
      {/* Category Filter & Search Bar */}
      <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs space-y-3">
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
          
          {/* Search within Category */}
          <div className="relative w-full sm:w-80">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder={`Search within ${categoryName}...`}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white"
            />
          </div>

          {/* Sort Selector */}
          <div className="flex items-center gap-2 w-full sm:w-auto justify-end">
            <span className="text-xs text-slate-500 font-mono hidden sm:inline">Sort:</span>
            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:border-emerald-500 font-semibold cursor-pointer"
            >
              <option value="best_price">Best Price (Low to High)</option>
              <option value="price_desc">Price (High to Low)</option>
              <option value="name">Product Name (A-Z)</option>
              <option value="newest">Featured & Newest</option>
            </select>
          </div>
        </div>

        {/* Brand Filter Pills */}
        {availableBrands.length > 1 && (
          <div className="flex items-center gap-1.5 overflow-x-auto pt-1 pb-1 scrollbar-thin text-xs">
            <button
              type="button"
              onClick={() => setSelectedBrand('all')}
              className={`px-3 py-1 rounded-full text-xs font-semibold shrink-0 transition-colors cursor-pointer ${
                selectedBrand === 'all'
                  ? 'bg-slate-900 text-white'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}
            >
              All Brands
            </button>
            {availableBrands.map((b) => (
              <button
                key={b.slug}
                type="button"
                onClick={() => setSelectedBrand(b.slug)}
                className={`px-3 py-1 rounded-full text-xs font-semibold shrink-0 transition-colors cursor-pointer ${
                  selectedBrand === b.slug
                    ? 'bg-emerald-600 text-white'
                    : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                {b.name}
              </button>
            ))}
          </div>
        )}
      </div>

      {/* Products Grid */}
      {loading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 animate-pulse">
          {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
            <div key={i} className="bg-white rounded-2xl p-5 h-80 border border-slate-200" />
          ))}
        </div>
      ) : filteredProducts.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
          {filteredProducts.map((product) => (
            <ProductCard key={product.id} product={product} market={market} />
          ))}
        </div>
      ) : (
        <EmptyState
          title={`No ${categoryName} match your criteria.`}
          description={`Try adjusting your search terms or filters for ${marketInfo.name}.`}
        />
      )}
    </div>
  );
};
