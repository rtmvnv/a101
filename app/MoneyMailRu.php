<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils;
use App\MoneyMailRuException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Модуль реализует взаимодействие с Mail.ru через API.
 * Описание протокола API2_03_08.pdf
 */
class MoneyMailRu
{
    const BASE_URI = 'https://api.money.mail.ru/money'; // URL для запросов, не включая номер версии API, без слеша

    private $key; // ключ доступа для формирования подписи запроса
    private $pay_method;
    private $merchant_id; // номер пользователя в Системе
    private $public_key; // ключ для проверки подписи ответа
    private $version; // Версия API Mail.ru

    public function __construct()
    {
        $this->merchant_id = config('services.money_mail_ru.merchant_id');
        $this->pay_method = config('services.money_mail_ru.pay_method');
        $this->key = config('services.money_mail_ru.key');
        $this->version = config('services.money_mail_ru.version');
        $this->public_key = file_get_contents(storage_path('app/' . config('services.money_mail_ru.public_key')));
    }

    public function request($action, $params = [])
    {
        /**
         * Подготовить данные запроса
         */
        $paramsObject = (object)[];
        $paramsObject->body = (object)$params;
        $paramsObject->header = (object)[];
        $paramsObject->header->ts = time();
        $paramsObject->header->client_id = $this->merchant_id;

        $paramsJson = json_encode($paramsObject);
        $data = base64_encode($paramsJson);

        $url = self::BASE_URI . '/' . $this->version . '/' . $action . '/';
        $urlArray = parse_url($url); // [ scheme => https, host => api.money.mail.ru, path => /money ]
        $urlArray['path'] = preg_replace('/\/$/', '', $urlArray['path']); // remove tailing slash

        $signatureString = $urlArray['path'] . $data . $this->key;
        $signature = sha1($signatureString);

        /**
         * Подготовить CURL запрос
         */
        $curl = curl_init();
        $curlopt = array();
        $curlopt[CURLOPT_URL] = $url;
        $curlopt[CURLOPT_HTTPHEADER] = array('Content-Type: application/x-www-form-urlencoded');
        $curlopt[CURLOPT_POST] = TRUE;
        $curlopt[CURLOPT_TIMEOUT_MS] = 5000;
        $curlopt[CURLOPT_RETURNTRANSFER] = TRUE;
        $curlopt[CURLOPT_FORBID_REUSE] = TRUE;
        $curlopt[CURLOPT_FRESH_CONNECT] = TRUE;
        $curlopt[CURLOPT_POSTFIELDS] = http_build_query(['data' => $data, 'signature' => $signature]);
        curl_setopt_array($curl, $curlopt);

        /*
         * Выполнить запрос
         */
        // $talkCode = \qubz\generateTalkCode();
        $request = ['action' => $action, 'body' => $paramsObject->body, 'header' => $paramsObject->header, 'data' => $data, 'signature' => $signature, 'url' => $curlopt[CURLOPT_URL]];
        if (!empty($request['header']->ts)) {
            $request['header']->ts_string = date('c', $request['header']->ts);
        }
        // $timeStart = $this->logSendRequest($request, $talkCode, 80405817);

        $curlResponse = curl_exec($curl);
        $info = curl_getinfo($curl);
        $info["errno"] = curl_errno($curl);
        $info["error"] = curl_error($curl);
        curl_close($curl);

        /*
         * Анализ ответа
         */
        try {
            $response = ['result_code' => 0, 'result_message' => 'success'];

            if ($curlResponse === FALSE) {
                $response['curl'] = $curlResponse;
                throw new MoneyMailRuException("CURL failed. URL:{$info["url"]}; errno:" . $info['errno'] . '; error:' . $info["error"], 11881481);
            };

            if ($info["http_code"] !== 200) {
                $response['curl'] = $curlResponse;
                throw new MoneyMailRuException("CURL http_code:{$info["http_code"]} url:{$info["url"]}", 34308418);
            }

            // Разбор текста ответа
            $mailruResponse = json_decode($curlResponse, true);
            if ($mailruResponse === null or empty($mailruResponse['data']) or empty($mailruResponse['signature'])) {
                throw new MoneyMailRuException("Некорректный ответ Mail.ru: " . print_r($curlResponse, true), 16093706);
            }
            $response = array_merge($response, $mailruResponse);

            // Проверить подпись
            $signature = base64_decode($mailruResponse['signature']);
            $verificationResult =  openssl_verify($mailruResponse['data'], $signature, $this->public_key);
            switch ($verificationResult) {
                case 1:
                    // Signature is correct
                    break;

                case 0:
                    throw new MoneyMailRuException("Mailru returned an incorrect signature", 48842114);
                    break;

                case -1:
                    throw new MoneyMailRuException("Error on signature verification", 74043881);
                    break;

                default:
                    throw new MoneyMailRuException("Unknown error on signature verification", 65060528);
                    break;
            }

            $dataString = base64_decode($mailruResponse['data'], true);
            if (!$dataString) {
                throw new MoneyMailRuException("base64_decode(data) failed '{$mailruResponse['data']}'", 75360949);
            }

            $data = json_decode($dataString, true);
            if (!$data) {
                throw new MoneyMailRuException("json_decode(data) failed '{$dataString}'", 97580775);
            }
            $response = array_merge($response, $data);

            if (!empty($data['header']['ts'])) {
                $data['header']['ts_string'] = date('c', $data['header']['ts']);
            }

            // if ($data['header']['status'] !== 'OK') {
            //     throw new MoneyMailRuException('Mailru reported status ' . $data['header']['status'], 49117962);
            // }

            // Расшифровать date в запросе merchant/history
            // foreach ($mailruResponse['body']['transactions'] as $key => $transaction) {
            //     if (isset($transaction['date'])) {
            //         $transaction['date_string'] = date('c', $transaction['date']);
            //     }
            //     $mailruResponse['body']['transactions'][$key] = $transaction;
            // }
        } catch (\Throwable $th) {
            $response['result_code'] = $th->getCode();
            $response['result_message'] = $th->getMessage();
        }

        // $this->logReceiveResponse(array('action' => $action, 'response' => $response, 'request' => $request, 'url' => $curlopt[CURLOPT_URL]), $talkCode, 61778102, $timeStart);

        return $response;
    }

    /**
     * Обработчик колбека от Mailru.
     *
     * Колбек содержит 3 POST поля:
     * {
     * 	"data" : "eyJib2R...19fQ==",
     * 	"signature" : "s7AMY...GH5roQ==",
     * 	"version" : "2-03"
     * }
     * 
     * Пример данных запроса
     * data = {
     * 	“body”: {
     * 		“notify_type":"TRANSACTION_STATUS",
     * 		“issuer_id":"864535d-5c88-4f65-81b1-fcf409f3c2ca",
     * 		“status":"PAID",
     * 		“added":"2018-07-04T17:27:37.000+03:00",
     * 		“user_info”: {
     * 			“user_id":"1046664",
     * 			“buyer_ip":"4.3.2.1",
     * 			“user_verified":"1"
     * 		},
     * 		“currency":"RUB",
     * 		“keep_uniq":"0",
     * 		“pay_system_name":"123456",
     * 		“payee_fee_amount":"5.40",
     * 		“payee_amount":"294.60",
     * 		“pay_method":"cpgtest",
     * 		“merchant_id":"123456",
     * 		“description":"test",
     * 		“merchant_name":"test",
     * 		“paid":"2018-07-04T17:27:48.000+03:00",
     * 		“amount":"300.00",
     * 		“transaction_id”:"66964534-7F96-11E8-B88E-2DB2D3562AF0"
     * 	},
     * 	“header”: {
     * 		“status":"OK",
     * 		“ts":"1530714471",
     * 		“client_id":"123456",
     * 		“error": {
     * 			“details":{}
     * 		}
     * 	}
     * }
     * 
     * Ответ на уведомление:
     * data = {
     * 	“body": {
     * 		“transaction_id":"66908AC4-7F96-8425-B88E-2DB2D3562AF0",
     * 		“notify_type":"TRANSACTION_STATUS"
     * 	},
     * 	“header": {
     * 		“status":"OK",
     * 		“ts":1530714471,
     * 		“client_id":"123456"
     * 	}
     * }
     * 
     * @param \Symfony\Component\HttpFoundation\Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws MoneyMailRuException
     */
    public function callback($httpRequest)
    {
        try {

            // Надо в любой момент иметь готовое значение $request чтобы даже при возникновении
            // исключения записать в лог полученный запрос
            $request = $httpRequest->getContent();
            $talkCode = \qubz\generateTalkCode();

            /*
                 * Проверить тип переданного аргумента
                 */
            if (get_class($httpRequest) != 'Symfony\Component\HttpFoundation\Request') {
                throw new MoneyMailRuException('$httpRequest should be of class Symfony\Component\HttpFoundation\Request. Class received ' . get_class($httpRequest), 84905683);
            }

            $request = $httpRequest->request->all();

            /*
             * Проверить подпись
             */
            $signature = base64_decode($request['signature']);
            $verificationResult = openssl_verify($request['data'], $signature, $this->public_key);
            switch ($verificationResult) {
                case 1:
                    // Signature is correct
                    break;

                case 0:
                    throw new MoneyMailRuException("Mailru callback has an incorrect signature", 42180260);
                    break;

                case -1:
                    throw new MoneyMailRuException("Error on callback signature verification", 81659319);
                    break;

                default:
                    throw new MoneyMailRuException("Unknown error on callback signature verification", 41249167);
                    break;
            }

            /*
                 * Расшифровать данные запроса
                 */
            $dataString = base64_decode($request['data']);
            if (!$dataString) {
                throw new MoneyMailRuException("base64_decode(data) failed '{$request['data']}'", 23374190);
            }

            $data = json_decode($dataString, true);
            if (!$data) {
                throw new MoneyMailRuException("json_decode(data) failed '{$dataString}'", 53969493);
            }

            if (!empty($data['header']['ts'])) {
                $data['header']['ts_string'] = date('c', $data['header']['ts']);
            }

            $request = array_merge($request, $data);
        } catch (\Throwable $th) {
            $this->logReceiveRequest($request, $talkCode, 72871046);
            $this->logException($th);

            $response = [
                'header' => [
                    'status' => 'ERROR',
                    'ts' => time(),
                    'error' => [
                        'code' => $th->getCode(),
                        'message' => $th->getMessage(),
                        'error_id' => 'talk_code_' . $talkCode
                    ]
                ]
            ];

            /*
                 * Ответить на запрос
                 */
            $this->logSendResponse($response, $talkCode, 56443165);
            $httpResponse = new \Symfony\Component\HttpFoundation\Response(\qubz\json_encode($response));
            $httpResponse->prepare($httpRequest);
            return $httpResponse;
        }

        $this->logReceiveRequest($request, $talkCode, 31079858);

        try {

            /*
                 * Вызвать обрабочик колбека прикладного модуля
                 * platon_menu->mailru_callback($data)
                 */
            $callback = $this->configGet('callback');
            $module = qubz()->factory($callback['module']);
            $response = call_user_func(array($module, $callback['function']), $data);
            if (!isset($response['header']['status'])) {
                $this->logDebug($response);
                throw new MoneyMailRuException('Error processing callback', 86904115);
            }
        } catch (\Throwable $th) {
            $this->logException($th);

            $response = [
                'header' => [
                    'status' => 'ERROR',
                    'ts' => time(),
                    'error' => [
                        'code' => $th->getCode(),
                        'message' => $th->getMessage(),
                        'error_id' => 'talk_code_' . $talkCode
                    ]
                ]
            ];
        }

        /*
             * Ответить на запрос
             */
        $this->logSendResponse($response, $talkCode, 39378338);
        $httpResponse = new \Symfony\Component\HttpFoundation\Response(\qubz\json_encode($response));
        $httpResponse->prepare($httpRequest);
        return $httpResponse;
    }

    public function responseError($code, $message, $error_id = '')
    {
        $response = [
            'header' => [
                'status' => 'ERROR',
                'ts' => time(),
                'error' => [
                    'code' => $code,
                    'message' => $message
                ]
            ]
        ];

        if (!empty($error_id)) {
            $response['header']['error']['error_id'] = $error_id;
        }

        return $response;
    }

    public function responseOK($client_id, $transaction_id)
    {
        return [
            'header' => [
                'status' => 'OK',
                'ts' => time(),
                'client_id' => $client_id
            ],
            'body' => [
                'transaction_id' => $transaction_id,
                'notify_type' => 'TRANSACTION_STATUS'
            ]
        ];
    }

    public function transactionStart(
        $userId,
        $amount,
        $description='',
        $issuerId='',
        $notifyEmail='',
        $backUrl='',
        $successUrl='',
        $failUrl='',
    )
    {
        // Пример ответа при временной ошибки
        // [result_code] => 11881481
        // [result_message] => CURL failed. URL:https://api.money.mail.ru/money/2-03/transaction/start/; errno:28; error:Resolving timed out after 5001 milliseconds
        // [curl] => 
        //
        // Пример ответа при окончательной ошибке
        // [result_code] => 0
        // [result_message] => success
        // [header] => [
        //     [status] => ERROR
        //     [ts] => 1623506206
        //     [error] => [
        //             [code] => ERR_ARGUMENTS
        //             [message] => 
        //             [error_id] => 068F3974-CB86-11EB-9BF9-8A1DB1A7499C
        //     ]
        // ]
        //
        // Пример ответа об успехе
        // [result_code] => 0
        // [result_message] => success
        // [body] => [
        //     [action_param] => [
        //             [url] => https://cpg.money.mail.ru/api/init/freepay?backurl=https%3A%2F%2Fpw.money.mail.ru%2Fpw%2Ftrampoline%2F2C74DBB6-CB87-11EB-9721-78EF65CFFB88&currency=643&extra=%7B%22light_id%22%3A252520,%22merchant_flag%22%3A0%7D&order_amount=10.00&order_id=2C74DBB6-CB87-11EB-9721-78EF65CFFB88&order_message=%D2%E5%F1%F2%EE%E2%FB%E9+%EF%EB%E0%F2%E5%E6&signature=ef81a5114347d275e0f4aa6a800be096770bb7c5&skin=STANDARD&user_ip=178.176.73.98&user_login=123&vterm_id=TestShops2
        //         ]
        //     [transaction_id] => 2C74DBB6-CB87-11EB-9721-78EF65CFFB88
        //     [action] => redirect
        // ]
        // [header] => [
        //     [status] => OK
        //     [ts] => 1623506699
        // ]

        $request = [
            'currency' => 'RUB',
            'pay_method' => $this->pay_method,
            'amount' => $amount,
            'user_info' => ['user_id' => $userId],
        ];

        if (empty($description)) {
            $request['description'] = 'Платеж на сумму '
                . number_format($amount, 2, ',', '.')
                . ' руб.';
        } else {
            $request['description'] = $description;
        }

        if (empty($issuerId)) {
            $request['issuer_id'] = (string) Str::uuid();
        } else {
            $request['issuer_id'] = $issuerId;
        }

        if (empty($notifyEmail)) {
            $request['user_info']['notify_email'] = $notifyEmail;
        }

        if (empty($backUrl)) {
            $request['notify']['back_url'] = $backUrl;
        }

        if (empty($successUrl)) {
            $request['notify']['success_url'] = $successUrl;
        }

        if (empty($failUrl)) {
            $request['notify']['fail_url'] = $failUrl;
        }

        $response =  $this->request('transaction/start', $request);

        // Записать лог об ошибке
        if ($response['result_code'] !== 0) {
            Log::warning('MoneyMailRu request failed. message:'
                . $response['result_message']);
        } elseif ($response['header']['status'] !== 'OK') {
            Log::warning('MoneyMailRu returned error. code:'
                . $response['header']['error']['code']
                . ' message:'
                . $response['header']['error']['message']
                . ' error_id:'
                . $response['header']['error']['error_id']);
        }

        return $response;
    }
}
