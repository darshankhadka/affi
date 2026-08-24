import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/** @type {import('next').NextConfig} */
const nextConfig = {
  // Static export for shared hosting / cPanel.
  // `next build` will generate the complete static site in /out.
  output: 'export',

  // Keep the project root correctly resolved for tracing/build tooling.
  outputFileTracingRoot: path.join(__dirname, '../'),

  // Generate directory-based static routes:
  // /us → /us/index.html
  // /us/about → /us/about/index.html
  // /us/products/catalog → /us/products/catalog/index.html
  trailingSlash: true,

  reactStrictMode: true,

  // Required for static export because Next.js Image Optimization
  // requires a running Next.js server.
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