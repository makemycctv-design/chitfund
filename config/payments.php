<?php

return [
    /*
     | Default gateway used for online payments. Manual (offline) collections
     | are always available to authorized staff regardless of this setting.
     */
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'razorpay'),

    'gateways' => [
        'manual' => [
            'driver' => 'manual',
        ],

        'razorpay' => [
            'driver' => 'razorpay',
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        ],

        // Cashfree / Stripe follow the same shape; add drivers as needed.
        'cashfree' => [
            'driver' => 'cashfree',
            'app_id' => env('CASHFREE_APP_ID'),
            'secret_key' => env('CASHFREE_SECRET_KEY'),
            'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
        ],

        'stripe' => [
            'driver' => 'stripe',
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],
];
