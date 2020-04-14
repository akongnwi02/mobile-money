<?php

return [

    'env' => env('APP_ENV', 'production'),
    
    'api_key' => env('APP_API_KEY'),

    'whitelist' => env('APP_IP_WHITELIST'),

    'debug' => env('APP_DEBUG', true),

    'partner_restriction' => env('APP_PARTNER_RESTRICTION', true),
    
    'search_cache_lifetime' => 10,
    
    /*
     * Services
     */
    
    'services' => [
        'iat' => [
            'code' => env('SERVICE_IAT_CODE'),
            'url' => env('SERVICE_IAT_URL'),
            'key' => env('SERVICE_IAT_KEY'),
        ],
        'eneo' => [
            'code' => env('SERVICE_ENEO_CODE'),
        ],
    ],
];