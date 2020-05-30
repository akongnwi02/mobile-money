<?php

return [
    
    'name' => env('APP_NAME'),
    
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
            'cashout_code' => env('SERVICE_ORANGE_CASHOUT_CODE'),
            'cashin_code' => env('SERVICE_ORANGE_CASHIN_CODE'),
            'key' => env('SERVICE_ORANGE_KEY'),
            'url' => env('SERVICE_ORANGE_URL'),
            'webpayment_url' => env('SERVICE_ORANGE_WEB_PAYMENT_URL'),
            'webpayment_code' => env('SERVICE_ORANGE_WEB_PAYMENT_CODE'),
            'webpayment_token' => env('SERVICE_ORANGE_WEB_PAYMENT_OAUTH_TOKEN'),
            'webpayment_api_username' => env('SERVICE_ORANGE_WEB_PAYMENT_API_USERNAME'),
            'webpayment_api_password' => env('SERVICE_ORANGE_WEB_PAYMENT_API_PASSWORD'),
            'webpayment_channel_msisdn' => env('SERVICE_ORANGE_WEB_PAYMENT_CHANNEL_MSISDN'),
            'webpayment_channel_pin' => env('SERVICE_ORANGE_WEB_PAYMENT_CHANNEL_PIN'),
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