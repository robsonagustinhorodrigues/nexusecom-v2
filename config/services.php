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

    'meli' => [
        'app_id' => env('MELI_CLIENT_ID'),
        'app_secret' => env('MELI_CLIENT_SECRET'),
        'redirect_uri' => env('MELI_REDIRECT_AUTH_URL', env('APP_URL').'/integrations/meli/auth/callback'),
        'notification_url' => env('MELI_NOTIFICATION_URL', env('APP_URL').'/webhooks/meli'),
    ],

    'bling' => [
        'api_key' => env('BLING_API_KEY'),
        'api_url' => env('BLING_API_URL', 'https://bling.com.br/Api/v6/'),
        'oauth_url' => env('BLING_OAUTH_URL', 'https://www.bling.com.br/Api/v3/oauth/authorize'),
        'client_id' => env('BLING_CLIENT_ID'),
        'client_secret' => env('BLING_CLIENT_SECRET'),
        'redirect_uri' => env('BLING_REDIRECT_URI'),
        'default_cfop' => env('BLING_NFE_DEFAULT_CFOP', '5102'),
        'default_csosn' => env('BLING_NFE_DEFAULT_CSOSN', '102'),
        'natureza_operacao' => env('BLING_NFE_OPERATION_NATURE', 'Saída'),
    ],

    'amazon' => [
        'client_id' => env('AMAZON_CLIENT_ID'),
        'client_secret' => env('AMAZON_CLIENT_SECRET'),
        'refresh_token' => env('AMAZON_REFRESH_TOKEN'),
        'marketplace_id' => env('AMAZON_MARKETPLACE_ID'),
        'seller_id' => env('AMAZON_SELLER_ID'),
        'region' => env('AMAZON_REGION', 'br'),
    ],
];
