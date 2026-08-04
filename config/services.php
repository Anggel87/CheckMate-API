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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'governance' => [
        'base_url' => env('GOVERNANCE_BASE_URL', 'http://localhost:8001/api/v1'),
        'popup_url' => env('GOVERNANCE_POPUP_URL', 'http://localhost:8001/governance/auth'),
        'client_id' => env('GOVERNANCE_CLIENT_ID'),
        'client_secret' => env('GOVERNANCE_CLIENT_SECRET'),
        'auth_cache_ttl' => env('GOVERNANCE_AUTH_CACHE_TTL', 120),
        // Debe coincidir EXACTO con CHECKMATE_WEB_CALLBACK_URL del .env de gobernanza,
        // que valida el redirect_uri contra ese valor y rechaza cualquier otro.
        'web_callback_url' => env('GOVERNANCE_WEB_CALLBACK_URL', 'http://localhost:8000/auth/callback'),
    ],

];
