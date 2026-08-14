<?php

return [
    'order_number_prefix' => 'CLR',

    'payment' => [
        'vietqr' => [
            'image_base_url' => env('VIETQR_IMAGE_BASE_URL', 'https://img.vietqr.io/image'),
            'bank_id' => env('VIETQR_BANK_ID', '970407'),
            'account_number' => env('VIETQR_BANK_ACCOUNT', '2005111818'),
            'account_name' => env('VIETQR_ACCOUNT_NAME', 'DO TRUNG QUAN'),
            'template' => env('VIETQR_TEMPLATE', '4ueHdQu'),
        ],
    ],

    'shipping' => [
        'driver' => env('SHIPPING_RATE_DRIVER', 'estimate'),

        'estimate' => [
            'base_fee' => (int) env('SHIPPING_ESTIMATE_BASE_FEE', 30000),
            'included_weight_grams' => (int) env('SHIPPING_ESTIMATE_INCLUDED_WEIGHT_GRAMS', 500),
            'additional_weight_block_grams' => (int) env('SHIPPING_ESTIMATE_ADDITIONAL_WEIGHT_BLOCK_GRAMS', 500),
            'additional_weight_fee' => (int) env('SHIPPING_ESTIMATE_ADDITIONAL_WEIGHT_FEE', 5000),
            'outside_urban_area_surcharge' => (int) env('SHIPPING_ESTIMATE_OUTSIDE_URBAN_SURCHARGE', 10000),
            'order_cutoff_hour' => (int) env('SHIPPING_ESTIMATE_ORDER_CUTOFF_HOUR', 15),
            'urban_cities' => ['ha noi', 'ho chi minh', 'da nang'],
        ],
    ],
];
