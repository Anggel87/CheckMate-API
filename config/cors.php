<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('CHECKMATE_WEB_URL', 'https://checkmateutt.site'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
