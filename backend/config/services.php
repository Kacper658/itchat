<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'przelewy24' => [
        'env'        => env('P24_ENV', 'sandbox'),
        'merchant_id'=> env('P24_MERCHANT_ID', 0),
        'pos_id'     => env('P24_POS_ID', 0),
        'crc'        => env('P24_CRC_KEY'),
        'api_key'    => env('P24_REST_API_KEY'),
        'return_url' => env('P24_RETURN_URL', 'http://localhost:3000/payment/status'),
    ],

    'payment' => [
        'default' => env('PAYMENT_PROVIDER', 'przelewy24'),
    ],

    'geoip' => [
        'db' => env('GEOIP_DB'),
    ],

];
