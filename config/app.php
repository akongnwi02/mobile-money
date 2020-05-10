<?php

return [

    'env' => env('APP_ENV', 'production'),
    
    'url' => env('APP_URL'),
    
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
            'cashin_code' => env('SERVICE_ORANGE_CASHIN_CODE'),
            'cashout_code' => env('SERVICE_ORANGE_CASHOUT_CODE'),
            'url' => env('SERVICE_ORANGE_URL'),
            'key' => env('SERVICE_ORANGE_KEY'),
        ],
        'express_union' => [
            'cashin_code' => env('SERVICE_EXPRESS_UNION_CASHIN_CODE'),
            'cashout_code' => env('SERVICE_EXPRESS_UNION_CASHOUT_CODE'),
            'url' => env('SERVICE_EXPRESS_UNION_URL'),
            'key' => env('SERVICE_EXPRESS_UNION_KEY'),
        ],
        'mtn' => [
            'cashin_code' => env('SERVICE_MTN_CASHIN_CODE'),
            'cashout_code' => env('SERVICE_MTN_CASHOUT_CODE'),
            'url' => env('SERVICE_MTN_URL'),
            'key' => env('SERVICE_MTN_KEY'),
            'environment' => env('SERVICE_MTN_TARGET_ENVIRONMENT'),
            'cashout_key' => env('SERVICE_MTN_CASHOUT_SUBSCRIPTION_KEY'),
            'cashin_key' => env('SERVICE_MTN_CASHIN_SUBSCRIPTION_KEY'),
        ]
    ],
];