<?php

namespace App\MoneyMailRu;

use App\MoneyMailRu\Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;

/**
 * Модуль реализует взаимодействие с Mail.ru через API.
 * Описание протокола API2_03_08.pdf
 */
class MoneyMailRu
{
    protected const BASE_URI = 'https://api.money.mail.ru/money'; // URL для запросов, не включая номер версии API, без слеша

    public function request($action, $params = [])
    {
        /**
         * Подготовить данные запроса
         */
        $dataArray = [
            'body' => $params,
            'header' => [
                'ts' => (string)time(),
                'client_id' => config('services.money_mail_ru.merchant_id')
            ]
        ];
        $data = base64_encode(json_encode($dataArray, JSON_FORCE_OBJECT));

        $url = self::BASE_URI . '/' . config('services.money_mail_ru.version') . '/' . $action . '/';
        $urlArray = parse_url($url); // [ scheme => https, host => api.money.mail.ru, path => /money ]
        $urlArray['path'] = preg_replace('/\/$/', '', $urlArray['path']); // remove tailing slash

        $signatureString = $urlArray['path'] . $data . config('services.money_mail_ru.key');
        $signature = sha1($signatureString);

        /**
         * Подготовить CURL запрос
         */
        $curl = curl_init();
        $curlopt = array();
        $curlopt[CURLOPT_URL] = $url;
        $curlopt[CURLOPT_HTTPHEADER] = array('Content-Type: application/x-www-form-urlencoded');
        $curlopt[CURLOPT_POST] = true;
        $curlopt[CURLOPT_TIMEOUT_MS] = 5000;
        $curlopt[CURLOPT_RETURNTRANSFER] = true;
        $curlopt[CURLOPT_FORBID_REUSE] = true;
        $curlopt[CURLOPT_FRESH_CONNECT] = true;
        $curlopt[CURLOPT_POSTFIELDS] = http_build_query(['data' => $data, 'signature' => $signature]);
        curl_setopt_array($curl, $curlopt);

        /**
         * Записать лог
         */
        $record = [];
        $record['request'] = [
            'action' => $action,
            'body' => $dataArray['body'],
            'header' => $dataArray['header'],
            'data' => $data,
            'signature' => $signature,
        ];

        if (!empty($request['header']['ts'])) {
            $request['header']['ts_string'] = date('c', $request['header']['ts']);
        }

        $record['request_url'] = $curlopt[CURLOPT_URL];
        $requestTime = CarbonImmutable::now();
        $record['request_time'] = $requestTime->format('Y-m-d\TH:i:s.uP');

        /*
         * Выполнить запрос
         */
        $curlResponse = curl_exec($curl);
        $info = curl_getinfo($curl);
        $info["errno"] = curl_errno($curl);
        $info["error"] = curl_error($curl);
        curl_close($curl);

        $responseTime = CarbonImmutable::now();

        /*
         * Анализ ответа
         */
        try {
            $response = ['result_code' => 0, 'result_message' => 'success'];

            if ($curlResponse === false) {
                $response['curl'] = $curlResponse;
                throw new Exception("CURL failed. URL:{$info["url"]}; errno:" . $info['errno'] . '; error:' . $info["error"], 11881481);
            };

            if ($info["http_code"] !== 200) {
                $response['curl'] = $curlResponse;
                throw new Exception("CURL http_code:{$info["http_code"]} url:{$info["url"]}", 34308418);
            }

            // Разбор текста ответа
            $mailruResponse = json_decode($curlResponse, true);
            if ($mailruResponse === null or empty($mailruResponse['data']) or empty($mailruResponse['signature'])) {
                throw new Exception("Некорректный ответ Mail.ru: " . print_r($curlResponse, true), 16093706);
            }
            $response = array_merge($response, $mailruResponse);

            // Проверить подпись
            $signature = base64_decode($mailruResponse['signature']);
            $public_key = file_get_contents(storage_path('app/' . config('services.money_mail_ru.public_key')));
            $verificationResult =  openssl_verify($mailruResponse['data'], $signature, $public_key);
            switch ($verificationResult) {
                case 1:
                    // Signature is correct
                    break;

                case 0:
                    throw new Exception("Mailru returned an incorrect signature", 48842114);
                    break;

                case -1:
                    throw new Exception("Error on signature verification", 74043881);
                    break;

                default:
                    throw new Exception("Unknown error on signature verification", 65060528);
                    break;
            }

            $dataString = base64_decode($mailruResponse['data'], true);
            if (!$dataString) {
                throw new Exception("base64_decode(data) failed '{$mailruResponse['data']}'", 75360949);
            }

            $data = json_decode($dataString, true);
            if (!$data) {
                throw new Exception("json_decode(data) failed '{$dataString}'", 97580775);
            }
            $response = array_merge($response, $data);

            if (!empty($data['header']['ts'])) {
                $data['header']['ts_string'] = date('c', $data['header']['ts']);
            }

            // if ($data['header']['status'] !== 'OK') {
            //     throw new MoneyMailRu\Exception('Mailru reported status ' . $data['header']['status'], 49117962);
            // }

            // Расшифровать date в запросе merchant/history
            // foreach ($mailruResponse['body']['transactions'] as $key => $transaction) {
            //     if (isset($transaction['date'])) {
            //         $transaction['date_string'] = date('c', $transaction['date']);
            //     }
            //     $mailruResponse['body']['transactions'][$key] = $transaction;
            // }
        } catch (\Throwable $th) {
            $response['result_code'] = 73846608;
            $response['result_message'] = $th->getMessage();
            $response['exception'] = [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ];
        } finally {
            $record['response'] = $response;
            if (!isset($responseTime)) {
                $responseTime = CarbonImmutable::now();
            }
            $record['response_time'] = $responseTime->format('Y-m-d\TH:i:s.uP');
            $record['elapsed'] = $responseTime->floatDiffInSeconds($requestTime);
            Log::info('outgoing-mailru', $record);
        }

        return $response;
    }

    public function transactionStart(
        $issuerId,
        $userId,
        $amount,
        $description = '',
        $notifyEmail = '',
        $backUrl = '',
        $successUrl = '',
        $failUrl = '',
    ) {
        // Пример ответа при временной ошибке
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
            'pay_method' => config('services.money_mail_ru.pay_method'),
            'amount' => $amount,
            'user_info' => ['user_id' => $userId],
            'keep_uniq' => 1,
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

        if (!empty($notifyEmail)) {
            $request['user_info']['notify_email'] = $notifyEmail;
        }

        if (!empty($backUrl)) {
            $request['notify']['back_url'] = $backUrl;
        }

        if (!empty($successUrl)) {
            $request['notify']['success_url'] = $successUrl;
        }

        if (!empty($failUrl)) {
            $request['notify']['fail_url'] = $failUrl;
        }

        $response =  $this->request('transaction/start', $request);

        // [
        //   'result_code' => 0,
        //   'result_message' => 'success',
        //   'signature' => 'PSTX71qP...We92UFNtPQ==',
        //   'version' => '2-03-38',
        //   'data' => 'eyJib2R5Ijp7ImFj...TE2MTYxIn19',
        //   'body' => [
        //     'action_param' => [
        //       'url' => 'https://cpg.money.mail.ru/api/init/freepay?backurl=https%3A%2F%2Fpw.money.mail.ru%2Fpw%2Ftrampoline%2F555FBE68-10BC-11EC-AFC7-7E585155D469&currency=643&extra=%7B%22light_id%22%3A252520,%22merchant_flag%22%3A0%7D&order_amount=483.70&order_id=555FBE68-10BC-11EC-AFC7-7E585155D469&order_message=%CE%EF%EB%E0%F2%E0+%EA%E2%E8%F2%E0%ED%F6%E8%E8+A101+%EF%EE+%EB%E8%F6%E5%E2%EE%EC%F3+%F1%F7%E5%F2%F3+%7B%7B+%C1%C2513014+%7D%7D+%E7%E0+%7B%7B+%F1%E5%ED%F2%FF%E1%F0%FC+2021+%7D%7D&signature=74df599c9c32f73d7d5f5cd9960cce2c7f7ecaf3&skin=STANDARD&user_ip=195.162.69.245&user_login=87d162ef-9d57-4b99-b1c3-53d0e5efcaec&vterm_id=TestShops2',
        //     ],
        //     'transaction_id' => '555FBE68-10BC-11EC-AFC7-7E585155D469',
        //     'action' => 'redirect',
        //   ],
        //   'header' => [
        //     'status' => 'OK',
        //     'ts' => '1631116161',
        //   ],
        // ]

        // Записать лог об ошибке
        if ($response['result_code'] !== 0) {
            Log::error('MoneyMailRu request failed', $response);
        } elseif ($response['header']['status'] !== 'OK') {
            Log::warning('MoneyMailRu returned error', $response);
        }

        return $response;
    }

    /**
     * Checks that can connect to MoneyMailRu
     */
    public function health() {
        $response = $this->request('merchant/info');

        if ($response['result_code'] !== 0) {
            return "status:error; result_code:{$response['result_code']}; result_message:{$response['result_message']}";
        }

        if ($response['header']['status'] !== 'OK') {
            return "status:{$response['header']['status']}; "
                . 'code:' . (isset($response['header']['error']['code']) ? $response['header']['error']['code'] : 'unknown') . '; '
                . 'message:' . (isset($response['header']['error']['message']) ? $response['header']['error']['message'] : 'unknown') . '; '
                . 'error_id:' . (isset($response['header']['error']['error_id']) ? $response['header']['error']['error_id'] : 'unknown');
        }

        return true;
    }
}
