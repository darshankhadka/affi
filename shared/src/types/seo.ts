export interface MetaData {
  title: string;
  description: string;
  canonical: string;
  robots?: string;
  openGraph?: {
    title: string;
    description: string;
    url: string;
    siteName: string;
    images?: {
      url: string;
      width?: number;
      height?: number;
      alt?: string;
    }[];
    locale: string;
    type: 'website' | 'article';
  };
  alternates?: {
    canonical: string;
    languages?: Record<string, string>; // hreflang mapping: { 'en-us': '...', 'en-gb': '...' }
  };
}

export interface BreadcrumbItem {
  name: string;
  item: string;
}

export interface StructuredProductSchema {
  '@context': 'https://schema.org';
  '@type': 'Product';
  name: string;
  description?: string;
  image?: string[];
  sku?: string;
  gtin13?: string;
  mpn?: string;
  brand: {
    '@type': 'Brand';
    name: string;
  };
  offers?: {
    '@type': 'AggregateOffer' | 'Offer';
    priceCurrency: string;
    lowPrice?: number;
    highPrice?: number;
    price?: number;
    offerCount?: number;
    availability: string; // 'https://schema.org/InStock'
    seller?: {
      '@type': 'Organization';
      name: string;
    };
    url?: string;
  };
}
