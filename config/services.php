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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ligdicash' => [
        'api_url' => env('LIGDICASH_API_URL', 'https://app.ligdicash.com/pay/v01/redirect/checkout-invoice'),
        'api_key' => env('LIGDICASH_API_KEY'),
        'auth_token' => env('LIGDICASH_AUTH_TOKEN'),
        'platform' => env('LIGDICASH_PLATFORM', 'live'), // test or live
        'callback_url' => env('LIGDICASH_CALLBACK_URL', env('APP_URL').'/api/webhooks/ligdicash'),
        'return_url' => env('LIGDICASH_RETURN_URL', env('APP_URL').'/payment/callback'),
        'cancel_url' => env('LIGDICASH_CANCEL_URL', env('APP_URL').'/payment/cancel'),
        'store_name' => env('LIGDICASH_STORE_NAME', 'Pouire'),
        'store_website' => env('LIGDICASH_STORE_WEBSITE', 'https://pouire.tangagroup.com/'),

        // OTP Configuration
        'otp_api_url' => env('LIGDICASH_OTP_API_URL', 'https://app.ligdicash.com/pay/v02'),
        'otp_enabled' => env('LIGDICASH_OTP_ENABLED', true),
        'otp_expiry_minutes' => 5,
        'max_otp_requests' => 3,
        'otp_providers' => ['ORANGE', 'LIGDICASH_WALLET'],
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

];
