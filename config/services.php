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
    ],

    /*
    | Temporary testing: skip PayBy gateway + verification for TBO hotel checkout.
    | Set HOTEL_PAYBY_SKIP_PAYMENT=true in .env to enable test bypass; leave false for live PayBy.
    */
    'hotel' => [
        'payby_skip_payment' => env('HOTEL_PAYBY_SKIP_PAYMENT', false),
    ],

    /*
    | Sabre REST/SOAP can exceed default Guzzle timeouts (often ~10s). Raise for shop, revalidate, PNR, ticket.
    */
    'sabre' => [
        'http_timeout' => (int) env('SABRE_HTTP_TIMEOUT', 90),
        'http_connect_timeout' => (int) env('SABRE_HTTP_CONNECT_TIMEOUT', 30),
        'ticket_printer_lniata' => env('SABRE_TICKET_PRINTER_LNIATA', 'FA8CFB'),
        'ticket_printer_country_code' => env('SABRE_TICKET_PRINTER_COUNTRY_CODE', 'TG'),
    ],

    /*
    | Agency FOP sent to Travelport on hold + ticket (legacy airBook used Credit, not Cash).
    */
    'travelport' => [
        'fop_type' => env('TRAVELPORT_FOP_TYPE', 'Credit'),
        'card_type' => env('TRAVELPORT_CARD_TYPE', 'VI'),
        'card_number' => env('TRAVELPORT_CARD_NUMBER', '4111111111111111'),
        'card_exp' => env('TRAVELPORT_CARD_EXP', '2028-01'),
        'card_cvv' => env('TRAVELPORT_CARD_CVV', '123'),
        'card_holder' => env('TRAVELPORT_CARD_HOLDER', 'Andaleeb Travel Agency'),
    ],
];
