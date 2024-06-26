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
        'from_email' => env('REPORTS_FROM_EMAIL'),
    ],

    'from' => [
        'a101' => [
            'email' => env('FROM_A101_EMAIL'),
            'name' => env('FROM_A101_NAME'),
        ],
        'overhaul' => [
            'email' => env('FROM_OVERHAUL_EMAIL'),
            'name' => env('FROM_OVERHAUL_NAME'),
        ],
    ],

    'money_mail_ru' => [
        'merchant_id' => env('MONEYMAILRU_MERCHANT_ID'), // номер пользователя в Системе
        'pay_method' => env('MONEYMAILRU_PAY_METHOD'),
        'key' => env('MONEYMAILRU_KEY'), // Закрытый ключ для формирования подписи запроса
        'version' => env('MONEYMAILRU_VERSION'), // Версия API Mail.ru
        'public_key' => env('MONEYMAILRU_PUBLIC_KEY'), // Открытый ключ Mail.ru для проверки подписи ответа
        'verify_signature' => true, // При unit-тестировании устанавливается в false, у нас нет закрытого ключа Mail.ru
    ],

    'payments_imap' => [
        'url' => env('PAYMENTS_IMAP_URL'), // "{imap.mail.ru:993/imap/ssl}INBOX"
        'username' => env('PAYMENTS_IMAP_USERNAME'),
        'password' => env('PAYMENTS_IMAP_PASSWORD'),
    ],

    'reconciliation' => [
        'from' => env('RECONCILIATION_FROM'),
        'subject' => env('RECONCILIATION_SUBJECT'),
    ],

    'orangedata' => [
        'inn' => env('ORANGEDATA_INN'), // ИНН организации
        'pass' => env('ORANGEDATA_PASS'), // Пароль к клиентскому сертификату
        'url' => env('ORANGEDATA_URL', 'https://api.orangedata.ru:12003'),
    ],
];
