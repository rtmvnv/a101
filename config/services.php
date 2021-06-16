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

    'unione' => [
        'api_key' => env('UNIONE_API_KEY'),
        'from_email' => env('UNIONE_FROM_EMAIL', ''),
        'from_name' => env('UNIONE_FROM_NAME', ''),        
    ],

    'money_mail_ru' => [
        'merchant_id' => env('MONEYMAILRU_MERCHANT_ID'),
        'pay_method' => env('MONEYMAILRU_PAY_METHOD'),
        'key' => env('MONEYMAILRU_KEY'),
        'version' => env('MONEYMAILRU_VERSION'),
        'public_key' => env('MONEYMAILRU_PUBLIC_KEY')
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
