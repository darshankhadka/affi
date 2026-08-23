import { MetadataRoute } from 'next';
import { fetchApi } from '@/lib/api';

export const dynamic = 'force-static';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://arikartech.com';
  const markets = ['us', 'uk', 'de', 'fr', 'es', 'it', 'nl', 'au', 'nz'];
  const staticPages = ['about', 'privacy', 'terms', 'disclosure', 'contact'];

  const entries: MetadataRoute.Sitemap = [
    {
      url: `${siteUrl}`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1.0,
    },
  ];

  // Market homepages & static pages
  for (const m of markets) {
    entries.push({
      url: `${siteUrl}/${m}`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.9,
    });

    for (const page of staticPages) {
      entries.push({
        url: `${siteUrl}/${m}/${page}`,
        lastModified: new Date(),
        changeFrequency: 'monthly',
        priority: 0.5,
      });
    }
  }

  // Fetch real categories and brands if available at build time
  try {
    const [categoriesRes, brandsRes, productsRes] = await Promise.all([
      fetchApi('/categories').catch(() => null),
      fetchApi('/brands').catch(() => null),
      fetchApi('/products?per_page=100').catch(() => null),
    ]);

    const categories = categoriesRes?.data || [];
    const brands = brandsRes?.data || [];
    const products = productsRes?.data || [];

    for (const m of markets) {
      for (const cat of categories) {
        if (cat.slug) {
          entries.push({
            url: `${siteUrl}/${m}/categories/${cat.slug}`,
            lastModified: new Date(),
            changeFrequency: 'weekly',
            priority: 0.8,
          });
        }
      }

      for (const brand of brands) {
        if (brand.slug) {
          entries.push({
            url: `${siteUrl}/${m}/brands/${brand.slug}`,
            lastModified: new Date(),
            changeFrequency: 'weekly',
            priority: 0.7,
          });
        }
      }

      for (const prod of products) {
        if (prod.slug) {
          entries.push({
            url: `${siteUrl}/${m}/products/${prod.slug}`,
            lastModified: new Date(prod.updated_at || new Date()),
            changeFrequency: 'daily',
            priority: 0.9,
          });
        }
      }
    }
  } catch {
    // If API is unconfigured/offline during build, default entries are safely returned
  }

  return entries;
}
