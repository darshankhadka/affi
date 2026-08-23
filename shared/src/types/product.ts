import { Brand } from './brand';
import { Category } from './category';
import { Offer, BestPrice } from './offer';

export type ProductIdentifierType = 'UPC' | 'EAN' | 'GTIN' | 'MPN' | 'ASIN' | 'SKU';

export interface ProductIdentifier {
  id: number;
  product_id: number;
  variant_id?: number | null;
  type: ProductIdentifierType;
  value: string;
  normalized_value: string;
}

export interface ProductSpecification {
  id: number;
  product_id: number;
  group_name: string; // e.g. "Processor", "Display", "Memory"
  spec_name: string; // e.g. "Cores", "Refresh Rate", "Capacity"
  spec_value: string;
  display_order: number;
}

export interface ProductImage {
  id: number;
  product_id: number;
  url: string;
  alt_text?: string | null;
  is_primary: boolean;
  display_order: number;
  width?: number | null;
  height?: number | null;
}

export interface ProductVariant {
  id: number;
  product_id: number;
  name: string; // e.g. "Space Gray / 16GB / 512GB"
  sku?: string | null;
  attributes: Record<string, string>; // { color: "Space Gray", ram: "16GB", storage: "512GB" }
  is_active: boolean;
  identifiers?: ProductIdentifier[];
}

export type ProductStatus = 'draft' | 'published' | 'archived';

export interface Product {
  id: number;
  brand_id: number;
  category_id: number;
  name: string;
  slug: string;
  model_number?: string | null;
  description?: string | null;
  short_description?: string | null;
  status: ProductStatus;
  release_date?: string | null;
  canonical_upc?: string | null;
  canonical_ean?: string | null;
  canonical_mpn?: string | null;
  primary_image_id?: number | null;
  brand?: Brand;
  category?: Category;
  primary_image?: ProductImage | null;
  images?: ProductImage[];
  specifications?: ProductSpecification[];
  variants?: ProductVariant[];
  identifiers?: ProductIdentifier[];
  best_price?: BestPrice | null;
  offers?: Offer[];
  created_at?: string;
  updated_at?: string;
}
