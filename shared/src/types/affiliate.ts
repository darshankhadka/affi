export interface AffiliateProvider {
  id: number;
  code: string; // amazon, awin, cj, impact, direct
  name: string;
  type: 'api' | 'datafeed' | 'manual';
  is_active: boolean;
  rate_limit_per_minute: number;
  status: 'connected' | 'disconnected' | 'error';
  last_sync_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface AffiliateAccount {
  id: number;
  provider_id: number;
  market_id: number;
  account_tag: string; // e.g. "arikartech-20" for US, "arikartech-21" for UK
  is_active: boolean;
}

export interface Retailer {
  id: number;
  name: string;
  slug: string;
  domain: string;
  logo_url?: string | null;
  affiliate_provider_id: number;
  affiliate_provider?: AffiliateProvider;
  is_active: boolean;
  offers_count?: number;
  created_at?: string;
  updated_at?: string;
}
