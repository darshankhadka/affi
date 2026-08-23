export interface Currency {
  id: number;
  code: string; // USD, GBP, EUR
  symbol: string; // $, £, €
  rate_to_usd: number;
  decimals: number;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface Country {
  id: number;
  iso_code_2: string; // US, GB, DE, FR
  iso_code_3: string; // USA, GBR, DEU, FRA
  name: string;
  currency_id: number;
  currency?: Currency;
  market_id: number;
}

export interface Market {
  id: number;
  code: string; // us, uk, de, fr, es, it, au, nz, etc.
  name: string; // United States, United Kingdom, Germany, etc.
  default_currency_id: number;
  default_currency?: Currency;
  locale: string; // en-US, en-GB, de-DE, fr-FR
  hreflang: string; // en-us, en-gb, de, fr
  is_active: boolean;
  countries?: Country[];
  created_at?: string;
  updated_at?: string;
}
