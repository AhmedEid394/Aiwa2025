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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'bank_misr' => [
    'api_url' => env('BANK_MISR_API_URL', 'https://10.232.15.5/EG-ACH-Corporate-Web-Station-MISR/CorpayWebAPI/api/Transactions'),
    'status_api_url' => env('BANK_MISR_STATUS_API_URL','https://10.232.15.5/EG-ACH-Corporate-Web-Station-MISR/CorpayWebAPI'),
    'private_key_path' => env('BANK_MISR_PRIVATE_KEY_PATH', storage_path('app/private/private_key.pem')),
    'testing_mode' => env('BANK_MISR_TESTING_MODE', true),
    'status_check_interval' => env('BANK_MISR_STATUS_CHECK_INTERVAL', 30),
    'processing_status_check_interval' => env('BANK_MISR_PROCESSING_CHECK_INTERVAL', 1440),
    ],

];
