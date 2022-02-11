<?php

namespace App\UniOne;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Carbon\CarbonImmutable;

class UniOne
{
    protected const BASE_URI = 'https://eu1.unione.io/ru/transactional/api/v1/';

    /**
     * Perform actual request to UniSender.
     *
     * Examples of respose:
     * error
     * [
     *   [status] => error
     *   [code] => 102
     *   [message] => Error ID:B4B650AE-C2C2-11EB-8122-AE60B670C2AF.
     * ]
     *
     * info request
     * [
     *   [status] => success
     *   [user_id] => 4471294
     *   [email] => grigorev_al@a101comfort.ru
     *   [accounting] => [
     *     [period_start] => 2021-05-25 10:56:02
     *     [period_end] => 2021-06-25 10:56:02
     *     [emails_included] => 0
     *     [emails_sent] => 0
     *   ]
     * ]
     *
     * @param  string  $uri
     * @param  string  $body
     *
     * @return array
     */
    public function request(string $uri, array $body = [])
    {
        /**
         * Лог запроса
         */
        $bodyLog = (array)$body;
        unset($bodyLog['message']['body']['html']);
        unset($bodyLog['message']['attachments']);
        unset($bodyLog['message']['inline_attachments']);

        $requestTime = CarbonImmutable::now();

        $record = [
            'request' => $bodyLog,
            'request_url' => $uri,
            'request_time' => $requestTime->format('c'),
        ];

        // Workaround for a bug on Unisender. To avoid error code 150:
        // вместо $obj = []; передавать $obj = {}; использовать явные кавычки {} для передачи.
        if (empty($body)) {
            $body = new \stdClass();
        }

        $client = new Client(['base_uri' => self::BASE_URI]);
        try {
            $response = $client->request(
                'POST',
                $uri,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'X-API-KEY' => config('services.unione.api_key')
                    ],
                    'json' => $body,
                    'http_errors' => false,
                ]
            );

            /**
             * Лог ответа
             */
            $record['response_code'] = $response->getStatusCode();
            $jsonResponse = json_decode($response->getBody(), true, 512);
            if (json_last_error() === JSON_ERROR_NONE) {
                // В ответе пришел JSON
                $record['response_type'] = 'json';
                $record['response'] = $jsonResponse;
            } else {
                // Ответ неструктурирован
                $record['response_type'] = 'raw';
                $record['response_raw'] = $response->getBody()->getContents();
            }
        } catch (\Throwable $th) {
            // В ответе пришло исключение
            $record['response_type'] = 'exception';
            $record['response']['exception'] = [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ];

            $responseTime = CarbonImmutable::now();
            $record['response_time'] = $responseTime->format('c');
            $record['elapsed'] = $responseTime->floatDiffInSeconds($requestTime);

            Log::info('outgoing-unione', $record);

            throw($th);
        }

        $responseTime = CarbonImmutable::now();
        $record['response_time'] = $responseTime->format('c');
        $record['elapsed'] = $responseTime->floatDiffInSeconds($requestTime);

        Log::info('outgoing-unione', $record);

        return json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Возвращает информацию о пользователе или проекте по API ключу.
     * https://docs.unione.ru/web-api-ref?php#system-info
     *
     * @return array
     */
    public function systemInfo()
    {
        return $this->request('system/info.json');
    }

    /**
     * Метод для отправки писем вашим подписчикам.
     * https://docs.unione.ru/web-api-ref?php#email
     *
     * @param  \App\UniOne\Message  $message
     *
     * @return array
     */
    public function emailSend($message)
    {
        return $this->request('email/send.json', $message->build());
    }

    /**
     * Возвращает текстовое пояснение статуса доставки письма
     * https://docs.unione.io/web-api-ref#callback-format
     */
    public static function explainError($status, $deliveryStatus, $destinationResponce)
    {
        if ($status == 'delivered') {
            return 'Письмо доставлено получателю';
        }

        if ($status == 'opened') {
            return 'Письмо доставлено и прочитано получателем';
        }

        if ($status == 'clicked') {
            return 'Письмо доставлено. Получатель перешел по ссылке из письма';
        }

        if ($status == 'soft_bounced') {
            return 'Продолжаются попытки доставки письма';
        }

        if ($status == 'spam') {
            return 'Письмо отмечено как spam на сервере получателя';
        }

        if ($status == 'unsubscribed') {
            return 'Письмо доставлено, получатель отписался от рассылки';
        }

        if ($status == 'hard_bounced') {
            $response = 'Письмо не удалось доставить. ';

            if ($deliveryStatus == 'err_user_unknown') {
                return $response . 'Несуществующий адрес.';
            }

            if ($deliveryStatus == 'err_mailbox_full') {
                return $response . 'Ящик получателя переполнен.';
            }

            if ($deliveryStatus == 'err_domain_inactive') {
                return $response . 'Несуществующий адрес, неизвестное доменное имя.';
            }

            if ($deliveryStatus == 'err_user_inactive') {
                return $response . 'Ящик пользователя выключен.';
            }

            if ($deliveryStatus == 'err_user_inactive') {
                return $response . 'Ящик пользователя выключен.';
            }

            if ($deliveryStatus == 'err_blacklisted') {
                return $response . 'Отклонено из-за черного списка.';
            }

            if ($deliveryStatus == 'err_spam_rejected') {
                return $response . 'Отклонено spam-фильтром.';
            }

            if ($deliveryStatus == 'err_destination_misconfigured') {
                return $response . 'Ошибка на сервере получателя.';
            }

            if ($deliveryStatus == 'err_delivery_failed') {
                return 'Письмо не удалось доставить';
            }

            return 'Письмо не удалось доставить';
        }
    }
}
