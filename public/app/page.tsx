'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function RootPage() {
  const router = useRouter();

  useEffect(() => {
    router.replace('/us');
  }, [router]);

  return (
    <html lang="en">
      <head>
        <meta httpEquiv="refresh" content="0; url=/us" />
        <title>ARIKARTECH — Technology Price Comparison</title>
      </head>
      <body className="bg-slate-50 min-h-screen flex items-center justify-center font-sans">
        <div className="text-center space-y-3">
          <div className="w-8 h-8 border-2 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="text-xs text-slate-500 font-medium">Redirecting to ARIKARTECH US...</p>
        </div>
      </body>
    </html>
  );
}
