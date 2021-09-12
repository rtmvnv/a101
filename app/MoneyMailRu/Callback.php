<?php

namespace App\MoneyMailRu;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\MoneyMailRu\Exception;

/**
 * Уведомление об успешном платеже (серверные платёжные уведомления)
 */
class Callback
{
    /*
    {
        "body": {
            "notify_type": "TRANSACTION_STATUS",
            "issuer_id": "29cc7c18-242d-4b11-93c4-506c8deaf986",
            "status": "PAID",
            "added": "2021-09-07T14:09:23.000+03:00",
            "txn_id": "10752588639032211961",
            "user_info": {
                "user_id": "6c565f5b-9e9e-48c6-9e3f-98003e5f1090"
            },
            "currency": "RUB",
            "keep_uniq": "0",
            "pay_system_name": "Бановские карты (ТЕСТ)",
            "payee_fee_amount": "25.60",
            "payee_amount": "486.23",
            "pay_method": "cpgtest",
            "merchant_id": "252520",
            "description": "Оплата квитанции A101 по лицевому счету {{ БВ668872 }} за {{ сентябрь 2021 }}",
            "merchant_name": "А101 Комфорт",
            "merchant_param": {},
            "paid": "2021-09-07T14:10:49.000+03:00",
            "amount": "511.83",
            "transaction_id": "0EEF9138-0FCC-11EC-89AC-9934AE8EF485"
        },
        "header": {
            "status": "OK",
            "ts": "1631013051",
            "client_id": "252520",
            "error": {
                "details": {}
            }
        }
    }
    */
    protected $data; // Строка data запроса
    protected $signature; // Строка signature запроса

    public $validationRequired = true;

    public $body;
    public $header;

    public function __construct(Request $request = null)
    {
        if (empty($request)) {
            $request = new Request();
        }

        $this->data = $request['data'];
        $this->signature = base64_decode($request['signature']);

        $json = base64_decode($request['data'], true);
        if ($json === false) {
            throw new Exception("base64_decode() failed '{$request['data']}'", 77808196);
        }
        $array = json_decode(base64_decode($request['data'], true), true);
        if (!$array) {
            throw new Exception("json_decode() failed '{$json}'", 71237043);
        }

        $this->body = $array['body'];
        $this->header = $array['header'];

        Log::info('mailru.callback.request', [
            'request' => ['body' => $this->body, 'header' => $this->header]
        ]);
    }

    public function validate()
    {
        if (config('services.money_mail_ru.verify_signature')) {
            // Verify signature
            $public_key = file_get_contents(storage_path('app/' . config('services.money_mail_ru.public_key')));
            if (openssl_verify($this->data, $this->signature, $public_key) !== 1) {
                return $this->respondError('Ошибка проверки подписи колбека', 'ERR_SIGNATURE');
            }
        }

        // Verify client_id
        if ($this->header['client_id'] != config('services.money_mail_ru.merchant_id')) {
            return $this->respondError('В колбеке указан неверный client_id', 'ERR_ARGUMENTS');
        }

        return true;
    }


    public function respondOk()
    {
        /*
        {
            "body": {
                "transaction_id": "66908AC4-7F96-8425-B88E-2DB2D3562AF0",
                "notify_type": "TRANSACTION_STATUS"
            },
            "header": {
                "status": "OK",
                "ts": 1530714471,
                "client_id": "123456"
            }
        }
        */

        $response = [
            'body' => [
                'transaction_id' => $this->body['transaction_id'],
                'notify_type' => $this->body['notify_type'],
            ],
            'header' => [
                'status' => 'OK',
                'ts' => (string)time(),
                'client_id' => config('services.money_mail_ru.merchant_id'),
            ]
        ];

        Log::info('mailru.callback.response', [
            'request' => ['body' => $this->body, 'header' => $this->header],
            'response' => $response
        ]);

        return self::encrypt($response);
    }

    public function respondError($message, $code = 'ERR_SYSTEM')
    {
        /*
        {
            "body": {
                "transaction_id": "81C10DD6-F575-3485-A6AD-EBF07900CD62",
                "notify_type": "TRANSACTION_STATUS"
            },
            "header": {
                "status": "ERROR",
                "ts": 1571822085,
                "client_id": "123456",
                "error": {
                    "code": "ERR_ARGUMENTS",
                    "message": "blablabla"
                }
            }
        }
        */
        $response = [
            'body' => [
                'transaction_id' => $this->body['transaction_id'],
                'notify_type' => $this->body['notify_type'],
            ],
            'header' => [
                'status' => 'ERROR',
                'ts' => (string)time(),
                'client_id' => config('services.money_mail_ru.merchant_id'),
                'error' => [
                    'code' => $code,
                    'message' => $message
                ]
            ]
        ];

        Log::info('mailru.callback.response', [
            'request' => ['body' => $this->body, 'header' => $this->header],
            'response' => $response
        ]);

        return self::encrypt($response);
    }

    /**
     * Возвращает сформированное сообщение об ошибке.
     * Используется если из поступившего запроса не удалось создать объект.
     */
    public static function respondFatal($message)
    {
        $response = [
            'body' => [
                'transaction_id' => 'unknown',
                'notify_type' => 'unknown'
            ],
            'header' => [
                'status' => 'ERROR',
                'ts' => (string)time(),
                'client_id' => config('services.money_mail_ru.merchant_id'),
                'error' => [
                    'code' => 'ERR_ARGUMENTS',
                    'message' => $message
                ]
            ]
        ];

        Log::info('mailru.callback.response', [
            'request' => ['body' => [], 'header' => []],
            'response' => $response
        ]);

        return self::encrypt($response);
    }

    public static function encrypt($data)
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_FORCE_OBJECT);
        }

        return json_encode(
            [
                'data' => base64_encode($data),
                'signature' => sha1(base64_encode($data) . config('services.money_mail_ru.key')),
                'version' => config('services.money_mail_ru.version')
            ],
            JSON_FORCE_OBJECT
        );
    }
}
