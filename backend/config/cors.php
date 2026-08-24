<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Production
        'https://arikartech.com',
        'https://www.arikartech.com',
        'https://admin.arikartech.com',
        'https://api.arikartech.com',
        // Local dev — HTTP (legacy)
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        // Local dev — HTTPS (mkcert)
        'https://localhost:3000',
        'https://localhost:5173',
        'https://127.0.0.1:3000',
        'https://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.arikartech\.com$#',
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
