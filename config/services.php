<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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


    'payway' => [
        'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
        'merchant_id' => env('PAYWAY_MERCHANT_ID'),
        'api_key' => env('PAYWAY_API_KEY'),
        // Full public HTTPS URL to the Laravel callback route (use ngrok for local testing).
        'callback_url' => env('PAYWAY_CALLBACK_URL'),
        'currency' => env('PAYWAY_CURRENCY', 'USD'),
        'payment_option' => env('PAYWAY_PAYMENT_OPTION', 'abapay_khqr'),
        'lifetime' => (int) env('PAYWAY_LIFETIME', 15),
        'qr_image_template' => env('PAYWAY_QR_IMAGE_TEMPLATE', 'template3_color'),
    ],

    'ecommerce' => [
        // Set this in .env when the shop charges for home delivery. Defaults to free delivery.
        'delivery_fee' => (float) env('WEB_DELIVERY_FEE', 0),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Must exactly match an Authorized redirect URI in Google Cloud Console.
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'group_chat_id' => env('TELEGRAM_GROUP_CHAT_ID'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'admin_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEGRAM_ADMIN_IDS', ''))
        ))),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
