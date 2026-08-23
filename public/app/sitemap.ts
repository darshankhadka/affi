import { MetadataRoute } from 'next';
import { CANONICAL_MARKETS, CANONICAL_CATEGORIES, CANONICAL_BRANDS } from '@/lib/catalog';

export const dynamic = 'force-static';

export default function sitemap(): MetadataRoute.Sitemap {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://arikartech.com';
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
  for (const m of CANONICAL_MARKETS) {
    entries.push({
      url: `${siteUrl}/${m.code}`,
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.9,
    });

    for (const page of staticPages) {
      entries.push({
        url: `${siteUrl}/${m.code}/${page}`,
        lastModified: new Date(),
        changeFrequency: 'monthly',
        priority: 0.5,
      });
    }

    for (const cat of CANONICAL_CATEGORIES) {
      entries.push({
        url: `${siteUrl}/${m.code}/categories/${cat.slug}`,
        lastModified: new Date(),
        changeFrequency: 'weekly',
        priority: 0.8,
      });
    }

    for (const brand of CANONICAL_BRANDS) {
      entries.push({
        url: `${siteUrl}/${m.code}/brands/${brand.slug}`,
        lastModified: new Date(),
        changeFrequency: 'weekly',
        priority: 0.7,
      });
    }
  }

  return entries;
}
