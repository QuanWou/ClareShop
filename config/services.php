<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Values are populated at runtime from encrypted Admin Settings.
    'google' => [
        'client_id' => null,
        'client_secret' => null,
        'redirect' => null,
    ],

    'facebook' => [
        'client_id' => null,
        'client_secret' => null,
        'redirect' => null,
    ],

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
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        'pending_minutes' => (int) env('MOMO_PENDING_MINUTES', 30),
        // MoMo recommends at least 30 seconds for captureWallet responses.
        'timeout_seconds' => (int) env('MOMO_TIMEOUT_SECONDS', 30),
        // External gateway calls are intentionally disabled for the test suite.
        'enabled' => filter_var(env('MOMO_ENABLED', false), FILTER_VALIDATE_BOOL)
            && filled(env('MOMO_PARTNER_CODE'))
            && filled(env('MOMO_ACCESS_KEY'))
            && filled(env('MOMO_SECRET_KEY')),
    ],

    'payos' => [
        'client_id' => env('PAYOS_CLIENT_ID'),
        'api_key' => env('PAYOS_API_KEY'),
        'checksum_key' => env('PAYOS_CHECKSUM_KEY'),
        'base_url' => env('PAYOS_BASE_URL', 'https://api-merchant.payos.vn'),
        'webhook_url' => env('PAYOS_WEBHOOK_URL'),
        'ip_resolve' => env('PAYOS_IP_RESOLVE', 'auto'),
        'max_retries' => (int) env('PAYOS_MAX_RETRIES', 2),
        'enabled' => filled(env('PAYOS_CLIENT_ID'))
            && filled(env('PAYOS_API_KEY'))
            && filled(env('PAYOS_CHECKSUM_KEY')),
    ],

];
