<?php

namespace App\Services\SEO;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Product;

class SitemapService
{
    /**
     * Generate master sitemap index XML
     */
    public function generateIndexXml(): string
    {
        $siteUrl = config('app.url', 'https://arikartech.com');
        $activeMarkets = Market::where('is_active', true)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($activeMarkets as $market) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$siteUrl}/sitemap-{$market->code}-products.xml</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";

            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$siteUrl}/sitemap-{$market->code}-categories.xml</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Generate product sitemap XML for a specific market
     */
    public function generateProductSitemapXml(Market $market): string
    {
        $siteUrl = config('app.url', 'https://arikartech.com');
        $products = Product::where('status', 'published')
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->select(['id', 'slug', 'updated_at'])
            ->limit(10000)
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($products as $product) {
            $lastmod = $product->updated_at ? $product->updated_at->format('Y-m-d') : date('Y-m-d');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$siteUrl}/{$market->code}/products/{$product->slug}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Generate categories & brands sitemap XML for a specific market
     */
    public function generateCategorySitemapXml(Market $market): string
    {
        $siteUrl = config('app.url', 'https://arikartech.com');
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Market Homepage
        $xml .= "  <url>\n";
        $xml .= "    <loc>{$siteUrl}/{$market->code}</loc>\n";
        $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
        $xml .= "    <changefreq>hourly</changefreq>\n";
        $xml .= "    <priority>1.0</priority>\n";
        $xml .= "  </url>\n";

        foreach ($categories as $category) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$siteUrl}/{$market->code}/categories/{$category->slug}</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>0.9</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }
}
