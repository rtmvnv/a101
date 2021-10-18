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
        'merchant_id' => env('MONEYMAILRU_MERCHANT_ID'), // номер пользователя в Системе
        'pay_method' => env('MONEYMAILRU_PAY_METHOD'),
        'key' => env('MONEYMAILRU_KEY'), // Закрытый ключ для формирования подписи запроса
        'version' => env('MONEYMAILRU_VERSION'), // Версия API Mail.ru
        'public_key' => env('MONEYMAILRU_PUBLIC_KEY'), // Открытый ключ Mail.ru для проверки подписи ответа
        'verify_signature' => true, // При unit-тестировании устанавливается в false, у нас нет закрытого ключа Mail.ru
    ],
    
];
