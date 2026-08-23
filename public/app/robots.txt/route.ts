import { NextResponse } from 'next/server';

export async function GET() {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'https://arikartech.com';

  const robots = `# ARIKARTECH Production Robots Directives
User-agent: *
Allow: /
Disallow: /api/
Disallow: /api/out/
Disallow: /*/search
Disallow: /*/compare?*

Sitemap: ${siteUrl}/sitemap.xml
`;

  return new NextResponse(robots, {
    headers: {
      'Content-Type': 'text/plain',
      'Cache-Control': 'public, max-age=86400, s-maxage=86400',
    },
  });
}
