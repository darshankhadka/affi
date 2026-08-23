'use client';

import React from 'react';
import Script from 'next/script';

export const GoogleAnalytics: React.FC<{ measurementId?: string }> = ({ measurementId }) => {
  const gaId = measurementId || process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID;

  if (!gaId) return null;

  return (
    <>
      <Script
        strategy="afterInteractive"
        src={`https://www.googletagmanager.com/gtag/js?id=${gaId}`}
      />
      <Script
        id="google-analytics"
        strategy="afterInteractive"
        dangerouslySetInnerHTML={{
          __html: `
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '${gaId}', {
              page_path: window.location.pathname,
              send_page_view: true
            });
          `,
        }}
      />
    </>
  );
};

export const trackEvent = (eventName: string, params: Record<string, any> = {}) => {
  if (typeof window !== 'undefined' && (window as any).gtag) {
    (window as any).gtag('event', eventName, params);
  }
};

export const trackAffiliateClick = (offerId: number, productName: string, retailerName: string, price: number, currency: string) => {
  trackEvent('affiliate_click', {
    offer_id: offerId,
    item_name: productName,
    retailer: retailerName,
    value: price,
    currency: currency,
  });
};
