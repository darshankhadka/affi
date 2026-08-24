<?php

namespace App\Services\SEO;

use App\Models\Market;
use App\Models\Product;

class StructuredDataService
{
    /**
     * Generate Schema.org Product and AggregateOffer / Offer JSON-LD schema
     */
    public function generateProductSchema(Product $product, Market $market): array
    {
        // Use frontend.url for all public-facing canonical product URLs
        $siteUrl = config('frontend.url', 'https://arikartech.com');
        // Affiliate click-tracking redirects live on the API domain
        $apiUrl  = config('app.url', 'https://api.arikartech.com');
        $productUrl = "{$siteUrl}/{$market->code}/products/{$product->slug}";
        
        $images = $product->images->pluck('url')->toArray();
        if (empty($images) && $product->primaryImage) {
            $images = [$product->primaryImage->url];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->short_description ?: strip_tags($product->description ?? ''),
            'url' => $productUrl,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand?->name ?? 'Generic',
            ],
        ];

        if (!empty($images)) {
            $schema['image'] = $images;
        }

        if ($product->model_number) {
            $schema['model'] = $product->model_number;
            $schema['mpn'] = $product->model_number;
        }

        if ($product->canonical_ean) {
            $schema['gtin13'] = $product->canonical_ean;
        } elseif ($product->canonical_upc) {
            $schema['gtin12'] = $product->canonical_upc;
        }

        $bestPrice = $product->bestPrices()->where('market_id', $market->id)->first();
        $offers = $product->offers()
            ->where('market_id', $market->id)
            ->where('is_active', true)
            ->with(['retailer', 'currency'])
            ->get();

        if ($offers->isNotEmpty()) {
            if ($offers->count() > 1 && $bestPrice) {
                $schema['offers'] = [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => $bestPrice->currency?->code ?? $market->defaultCurrency?->code ?? 'USD',
                    'lowPrice' => (float) $bestPrice->min_price,
                    'highPrice' => (float) $bestPrice->max_price,
                    'offerCount' => $offers->count(),
                    'offers' => $offers->map(function ($offer) use ($siteUrl) {
                        return [
                            '@type' => 'Offer',
                            'price' => (float) $offer->price,
                            'priceCurrency' => $offer->currency?->code ?? 'USD',
                            'availability' => $offer->availability === 'in_stock' 
                                ? 'https://schema.org/InStock' 
                                : 'https://schema.org/OutOfStock',
                            'itemCondition' => $offer->condition === 'new' 
                                ? 'https://schema.org/NewCondition' 
                                : 'https://schema.org/RefurbishedCondition',
                            'seller' => [
                                '@type' => 'Organization',
                                'name' => $offer->retailer?->name ?? 'Retailer',
                            ],
                            'url' => "{$apiUrl}/api/v1/affiliates/out/{$offer->id}",
                        ];
                    })->values()->toArray(),
                ];
            } else {
                $offer = $offers->first();
                $schema['offers'] = [
                    '@type' => 'Offer',
                    'price' => (float) $offer->price,
                    'priceCurrency' => $offer->currency?->code ?? 'USD',
                    'availability' => $offer->availability === 'in_stock' 
                        ? 'https://schema.org/InStock' 
                        : 'https://schema.org/OutOfStock',
                    'itemCondition' => $offer->condition === 'new' 
                        ? 'https://schema.org/NewCondition' 
                        : 'https://schema.org/RefurbishedCondition',
                    'seller' => [
                        '@type' => 'Organization',
                        'name' => $offer->retailer?->name ?? 'Retailer',
                    ],
                    'url' => "{$apiUrl}/api/v1/affiliates/out/{$offer->id}",
                ];
            }
        }

        return $schema;
    }

    /**
     * Generate Schema.org BreadcrumbList
     */
    public function generateBreadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(function ($crumb, $index) {
                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ];
            }, $crumbs, array_keys($crumbs)),
        ];
    }

    /**
     * Generate Schema.org WebSite schema with SearchAction
     */
    public function generateWebSiteSchema(Market $market): array
    {
        $siteUrl = config('frontend.url', 'https://arikartech.com');
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'ARIKARTECH',
            'url' => "{$siteUrl}/{$market->code}",
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => "{$siteUrl}/{$market->code}/search?q={search_term_string}",
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
