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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],

    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_CALLBACK'),
    ],

    'payby' => [
        'partner_id' => env('PAYBY_PARTNER_ID', '200009116289'),
        'api_url' => env('PAYBY_API_URL', 'https://api.payby.com/sgs/api/acquire2'),
        'private_key_path' => env('PAYBY_PRIVATE_KEY_PATH', 'app/keys/Merchant_private_key.pem'),
    ],

    'tabby' => [
        'api_key' => env('TABBY_API_KEY'),
        'api_url' => env('TABBY_API_URL', 'https://api.tabby.ai'),
        'merchant_code' => env('TABBY_MERCHANT_CODE'),
    ]
];
