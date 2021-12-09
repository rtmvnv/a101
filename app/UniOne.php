<?php

namespace App;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils;
use Carbon\CarbonImmutable;
use App\UniOneMessage;

class UniOne
{
    const BASE_URI = 'https://eu1.unione.io/ru/transactional/api/v1/';

    /**
     * Perform actual request to UniSender.
     *
     * Examples of respose:
     * error
     * [
     *   [status] => error
     *   [code] => 102
     *   [message] => Error ID:B4B650AE-C2C2-11EB-8122-AE60B670C2AF. Can not decode key 6gsp8885z45a4eoidgqdy4c9h5uez6nmxkf7mkyea
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
                    'http_errors' => false
                ]
            );
            $responseTime = CarbonImmutable::now();

            /**
             * Лог ответа
             */
            $jsonResponse = json_decode($response->getBody(), true, 512, JSON_OBJECT_AS_ARRAY);
            if (json_last_error() === JSON_ERROR_NONE) {
                // В ответе пришел JSON
                $record['response']['content'] = $jsonResponse;
            } else {
                // Ответ неструктурирован
                $record['response']['raw'] = $response->getBody();
            }
        } catch (\Throwable $th) {
            $responseTime = CarbonImmutable::now();
            $record['response'] = [
                'exception' => [
                    'message' => $th->getMessage(),
                    'code' => $th->getCode(),
                    'file' => $th->getFile(),
                    'line' => $th->getLine(),
                ],
            ];
        }

        $record['response_time'] = $responseTime->format('c');
        $record['elapsed'] = $responseTime->floatDiffInSeconds($requestTime);

        Log::info('outgoing-unione', $record);

        return json_decode($response->getBody(), true, 10, JSON_THROW_ON_ERROR);
    }

    /**
     * Возвращает информацию о пользователе или проекте по API ключу.
     * https://docs.unione.ru/web-api-ref?php#system-info
     *
     * @param  string  $uri
     * @param  string  $body
     *
     * @return array
     */
    public function systemInfo()
    {
        return $this->request('system/info.json');
    }
}
