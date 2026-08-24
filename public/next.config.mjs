import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
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