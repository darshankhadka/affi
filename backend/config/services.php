<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'amazon' => [
        'paapi_key' => env('AMAZON_PAAPI_KEY', env('AWS_ACCESS_KEY_ID')),
        'paapi_secret' => env('AMAZON_PAAPI_SECRET', env('AWS_SECRET_ACCESS_KEY')),
        'tags' => [
            'us' => env('AMAZON_TAG_US', 'arikartech-20'),
            'uk' => env('AMAZON_TAG_UK', 'arikartechuk-21'),
            'de' => env('AMAZON_TAG_DE', 'arikartechde-21'),
            'fr' => env('AMAZON_TAG_FR', 'arikartechfr-21'),
            'es' => env('AMAZON_TAG_ES', 'arikarteches-21'),
            'it' => env('AMAZON_TAG_IT', 'arikartechit-21'),
            'au' => env('AMAZON_TAG_AU', 'arikartechau-22'),
        ],
    ],

    'awin' => [
        'api_token' => env('AWIN_API_TOKEN'),
        'publisher_id' => env('AWIN_PUBLISHER_ID'),
        'datafeed_api_key' => env('AWIN_DATAFEED_API_KEY', env('AWIN_API_TOKEN')),
        'datafeed_url' => env('AWIN_DATAFEED_URL'),
    ],

    'cj' => [
        'api_token' => env('CJ_API_TOKEN'),
        'company_id' => env('CJ_COMPANY_ID'),
        'website_id' => env('CJ_WEBSITE_ID'),
    ],

    'impact' => [
        'account_sid' => env('IMPACT_ACCOUNT_SID'),
        'auth_token' => env('IMPACT_AUTH_TOKEN'),
        'media_partner_id' => env('IMPACT_MEDIA_PARTNER_ID'),
    ],

];
