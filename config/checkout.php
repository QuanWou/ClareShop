<?php

return [
    'order_number_prefix' => 'CLR',

    'voucher' => [
        'pending_minutes' => (int) env('VOUCHER_RESERVATION_MINUTES', 30),
    ],

    'payment' => [
        // Short-lived payment sessions protect QR codes from being reused.
        'qr_timeout_seconds' => (int) env('CHECKOUT_QR_TIMEOUT_SECONDS', 180),
        'paypal' => [
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            'currency' => env('PAYPAL_CURRENCY', 'USD'),
            'vnd_per_unit' => (int) env('PAYPAL_VND_PER_USD', 25000),
            'pending_minutes' => (int) env('PAYPAL_PENDING_MINUTES', 30),
            'timeout_seconds' => (int) env('PAYPAL_TIMEOUT_SECONDS', 20),
        ],
        'momo' => [
            'enabled' => filter_var(env('MOMO_ENABLED', false), FILTER_VALIDATE_BOOL)
                && filled(env('MOMO_PARTNER_CODE'))
                && filled(env('MOMO_ACCESS_KEY'))
                && filled(env('MOMO_SECRET_KEY')),
        ],
    ],

    'payment_methods' => [
        'cod' => [
            'label' => 'Thanh toán khi nhận hàng',
            'short_label' => 'COD',
            'description' => 'Thanh toán trực tiếp khi đơn được giao đến bạn.',
            'provider' => 'cod',
            'initial_status' => 'unpaid',
            'requires_qr' => false,
            'is_simulated' => false,
            'confirmation_title' => 'Thanh toán khi đơn được giao.',
            'confirmation_description' => 'Vui lòng chuẩn bị đúng tổng tiền khi nhận hàng.',
        ],
        'bank_transfer' => [
            'label' => 'QR ngân hàng qua payOS',
            'short_label' => 'payOS',
            'description' => 'Quét mã QR ngân hàng được payOS tạo riêng cho đơn hàng và tự động đối soát.',
            'provider' => 'payos',
            'initial_status' => 'pending',
            'requires_qr' => true,
            'is_simulated' => ! (filled(env('PAYOS_CLIENT_ID'))
                && filled(env('PAYOS_API_KEY'))
                && filled(env('PAYOS_CHECKSUM_KEY'))),
            'confirmation_title' => 'Quét mã payOS để thanh toán.',
            'confirmation_description' => 'Đơn được xác nhận tự động sau khi payOS gửi webhook hợp lệ.',
        ],
        'momo' => [
            'label' => 'Ví MoMo',
            'short_label' => 'MoMo',
            'description' => 'Thanh toán an toàn qua cổng MoMo. Bạn sẽ được chuyển đến MoMo để xác nhận giao dịch.',
            'provider' => 'momo',
            'initial_status' => 'pending',
            'requires_qr' => false,
            'is_simulated' => ! (filter_var(env('MOMO_ENABLED', false), FILTER_VALIDATE_BOOL)
                && filled(env('MOMO_PARTNER_CODE'))
                && filled(env('MOMO_ACCESS_KEY'))
                && filled(env('MOMO_SECRET_KEY'))),
            'confirmation_title' => 'Hoàn tất thanh toán bằng MoMo.',
            'confirmation_description' => 'Bạn sẽ được chuyển sang MoMo để xác nhận giao dịch. Đơn chỉ được xác nhận sau khi Clare nhận webhook có chữ ký hợp lệ.',
        ],
        'paypal' => [
            'label' => 'PayPal',
            'short_label' => 'PayPal',
            'description' => 'Thanh toán an toàn qua PayPal. Số tiền VND được quy đổi sang USD theo tỷ giá hiển thị trước khi chuyển hướng.',
            'provider' => 'paypal',
            'initial_status' => 'pending',
            'requires_qr' => false,
            'is_simulated' => false,
            'confirmation_title' => 'Hoàn tất thanh toán bằng PayPal.',
            'confirmation_description' => 'Bạn sẽ được chuyển sang PayPal để duyệt giao dịch và quay lại Clare sau khi thanh toán.',
        ],
        'pay_later' => [
            'label' => 'Mua trước, trả sau',
            'short_label' => 'Trả sau',
            'description' => 'Lựa chọn trả sau được ghi nhận và chờ đối tác tín dụng xét duyệt.',
            'provider' => 'pay_later_review',
            'initial_status' => 'pending',
            'requires_qr' => false,
            'is_simulated' => true,
            'confirmation_title' => 'Yêu cầu trả sau đang chờ xét duyệt.',
            'confirmation_description' => 'Clare đã ghi nhận lựa chọn của bạn. Hệ thống chưa tự duyệt hạn mức hoặc tạo khoản vay.',
        ],
    ],

    'shipping' => [
        'driver' => env('SHIPPING_RATE_DRIVER', 'estimate'),
        'default_option' => env('SHIPPING_DEFAULT_OPTION', 'ghn'),

        'estimate' => [
            'order_cutoff_hour' => (int) env('SHIPPING_ESTIMATE_ORDER_CUTOFF_HOUR', 15),
            'urban_cities' => ['ha noi', 'ho chi minh', 'da nang'],
        ],

        'providers' => [
            'ghn' => [
                'label' => 'Giao Hàng Nhanh (GHN)',
                'service' => 'Tiêu chuẩn',
                'description' => 'Giao tiêu chuẩn, ưu tiên các khu vực nội thành.',
                'base_fee' => 28000,
                'included_weight_grams' => 500,
                'additional_weight_block_grams' => 500,
                'additional_weight_fee' => 5500,
                'outside_urban_area_surcharge' => 12000,
                'urban_estimated_days' => 2,
                'outside_urban_estimated_days' => 4,
            ],
            'ghtk' => [
                'label' => 'Giao Hàng Tiết Kiệm (GHTK)',
                'service' => 'Tiết kiệm',
                'description' => 'Mức phí tiết kiệm cho đơn giao thông thường.',
                'base_fee' => 25000,
                'included_weight_grams' => 500,
                'additional_weight_block_grams' => 500,
                'additional_weight_fee' => 4500,
                'outside_urban_area_surcharge' => 10000,
                'urban_estimated_days' => 3,
                'outside_urban_estimated_days' => 5,
            ],
            'jnt' => [
                'label' => 'J&T Express',
                'service' => 'Tiêu chuẩn',
                'description' => 'Dịch vụ giao tiêu chuẩn của J&T Express.',
                'base_fee' => 32000,
                'included_weight_grams' => 500,
                'additional_weight_block_grams' => 500,
                'additional_weight_fee' => 6000,
                'outside_urban_area_surcharge' => 8000,
                'urban_estimated_days' => 2,
                'outside_urban_estimated_days' => 5,
            ],
        ],
    ],
];
