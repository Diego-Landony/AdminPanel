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

    /*
    |--------------------------------------------------------------------------
    | OAuth Social Authentication
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', rtrim(env('APP_URL'), '/').'/api/v1/auth/oauth/google/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    */

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/credentials.json')),
        'project_id' => env('FIREBASE_PROJECT_ID', 'flutter-subwayapp'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Apple Wallet (Passbook)
    |--------------------------------------------------------------------------
    */

    'apple_wallet' => [
        'certificate_path' => env('APPLE_WALLET_CERTIFICATE_PATH', storage_path('app/wallet/apple/certificates/pass.pem')),
        'certificate_password' => env('APPLE_WALLET_CERTIFICATE_PASSWORD', ''),
        'wwdr_certificate_path' => env('APPLE_WALLET_WWDR_CERTIFICATE_PATH', storage_path('app/wallet/apple/certificates/wwdr.pem')),
        'pass_type_identifier' => env('APPLE_WALLET_PASS_TYPE_ID', 'pass.com.subwayguatemala.loyalty'),
        'team_identifier' => env('APPLE_WALLET_TEAM_ID'),
        'organization_name' => env('APPLE_WALLET_ORG_NAME', 'Subway Guatemala'),
        'images_path' => env('APPLE_WALLET_IMAGES_PATH', storage_path('app/wallet/apple/images')),
        // Push Notifications Configuration
        'web_service_url' => env('APPLE_WALLET_WEB_SERVICE_URL'),
        'auth_secret' => env('APPLE_WALLET_AUTH_SECRET'),
        'apns_production' => env('APPLE_WALLET_APNS_PRODUCTION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Wallet
    |--------------------------------------------------------------------------
    */

    'google_wallet' => [
        'service_account_path' => env('GOOGLE_WALLET_SERVICE_ACCOUNT_PATH', storage_path('app/wallet/google/service-account.json')),
        'issuer_id' => env('GOOGLE_WALLET_ISSUER_ID'),
        'class_id' => env('GOOGLE_WALLET_CLASS_ID', 'subway_guatemala_loyalty'),
        'program_name' => env('GOOGLE_WALLET_PROGRAM_NAME', 'Subway Guatemala Rewards'),
    ],

];
