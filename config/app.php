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
        'orange' => [
            'code' => env('SERVICE_ORANGE_CODE'),
            'url' => env('SERVICE_ORANGE_URL'),
            'key' => env('SERVICE_ORANGE_KEY'),
        ],
        'express_union' => [
            'code' => env('SERVICE_EXPRESS_UNION_CODE'),
            'url' => env('SERVICE_EXPRESS_UNION_URL'),
            'key' => env('SERVICE_EXPRESS_UNION_KEY'),
        ],
    ],
];