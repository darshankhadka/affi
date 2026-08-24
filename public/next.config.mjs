import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// NEXT_PHASE is set by Next.js:
//   'phase-production-build'   →  next build
//   'phase-development-server' →  next dev
//   'phase-export'             →  next export (legacy)
const isProductionBuild = process.env.NEXT_PHASE === 'phase-production-build';

/** @type {import('next').NextConfig} */
const nextConfig = {
  // output: 'export' is ONLY applied during `next build` (static site generation).
  // During `next dev`, omitting it allows dynamic routing to work normally,
  // preventing the "missing param in generateStaticParams()" 500 error when
  // visiting real product URLs that aren't pre-declared in generateStaticParams().
  ...(isProductionBuild ? { output: 'export' } : {}),

  outputFileTracingRoot: path.join(__dirname, '../'),

  // Generate directory-based static routes:
  // /us → /us/index.html
  // /us/about → /us/about/index.html
  // /us/products/catalog → /us/products/catalog/index.html
  trailingSlash: true,

  reactStrictMode: true,

  images: {
    unoptimized: true,
    remotePatterns: [
      {
        protocol: 'https',
        hostname: '**',
      },
    ],
  },
};

export default nextConfig;