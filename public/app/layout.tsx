import type { Metadata } from 'next';
import './globals.css';
import { GoogleAnalytics } from '@/components/seo/GoogleAnalytics';

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL || 'https://arikartech.com'),
  title: {
    template: '%s | ARIKARTECH',
    default: 'ARIKARTECH | Global Tech Discovery & Price Comparison',
  },
  description: 'Compare tech prices, track hardware deals, and discover the best prices on laptops, GPUs, CPUs, monitors, and components across verified retailers.',
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      'max-video-preview': -1,
      'max-image-preview': 'large',
      'max-snippet': -1,
    },
  },
  icons: {
    icon: '/favicon.svg',
    shortcut: '/favicon.svg',
    apple: '/favicon.svg',
  },
  manifest: '/manifest.json',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap"
          rel="stylesheet"
        />
      </head>
      <body className="bg-slate-50 text-slate-900 min-h-screen flex flex-col font-sans antialiased">
        <GoogleAnalytics />
        {children}
      </body>
    </html>
  );
}
