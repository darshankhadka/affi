<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend (Public Site) URL
    |--------------------------------------------------------------------------
    |
    | The canonical URL of the public-facing Next.js storefront.
    | This is DISTINCT from APP_URL (which may be the API domain) and from
    | the API base URL.
    |
    | Used by:
    |   - MetadataService   → canonical, hreflang, Open Graph URLs
    |   - StructuredDataService → schema.org product/offer URLs
    |   - CatalogSeoAuditCommand → sitemap eligibility checks
    |
    | Production:  https://arikartech.com
    | Local dev:   https://127.0.0.1:3000
    |
    */
    'url' => env('FRONTEND_URL', 'https://arikartech.com'),
];
