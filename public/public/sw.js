// ARIKARTECH Service Worker
const CACHE_NAME = 'arikartech-cache-v1';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  // Cache-first strategy for static assets, network-first for pages and API
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Skip API and tracking URLs
  if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/go/')) {
    return;
  }
});
