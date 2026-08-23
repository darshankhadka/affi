/** @type {import('next').NextConfig} */

const nextConfig = {
  output: 'export',

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