import { Currency, Market } from './market';
import { Retailer } from './affiliate';

export type OfferAvailability = 'in_stock' | 'out_of_stock' | 'preorder' | 'discontinued';
export type OfferCondition = 'new' | 'refurbished' | 'used';

export interface Offer {
  id: number;
  product_id: number;
  variant_id?: number | null;
  retailer_id: number;
  market_id: number;
  currency_id: number;
  sku?: string | null;
  title: string;
  affiliate_url: string;
  original_url?: string | null;
  price: number;
  original_price?: number | null;
  discount_percentage?: number | null;
  shipping_cost?: number | null;
  availability: OfferAvailability;
  condition: OfferCondition;
  is_active: boolean;
  last_checked_at?: string | null;
  next_check_at?: string | null;
  error_count: number;
  retailer?: Retailer;
  market?: Market;
  currency?: Currency;
  created_at?: string;
  updated_at?: string;
}

export interface BestPrice {
  id: number;
  product_id: number;
  market_id: number;
  currency_id: number;
  min_price: number;
  max_price: number;
  best_offer_id: number;
  offer_count: number;
  in_stock_offer_count: number;
  best_offer?: Offer | null;
  currency?: Currency;
  market?: Market;
  updated_at: string;
}

export interface PriceHistory {
  id: number;
  offer_id: number;
  product_id: number;
  market_id: number;
  currency_id: number;
  price: number;
  original_price?: number | null;
  availability: OfferAvailability;
  recorded_at: string;
}
