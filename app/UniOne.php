<?php

namespace App;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils;
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
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-KEY' => config('services.unione.api_key')
        ];

        /**
         * Лог запроса
         */
        $bodyLog = (array)$body;
        unset($bodyLog['message']['body']['plaintext']);
        unset($bodyLog['message']['body']['html']);
        unset($bodyLog['message']['attachments']);
        Log::info('unione.request', ['request' => ['uri' => $uri, 'body' => $bodyLog]]);

        // Workaround for a bug on Unisender. To avoid error code 150:
        // вместо $obj = []; передавать $obj = {}; использовать явные кавычки {} для передачи.
        if (empty($body)) {
            $body = new \stdClass();
        }

        $client = new Client(['base_uri' => self::BASE_URI]);
        $response = $client->request(
            'POST',
            $uri,
            [
                'headers' => $headers,
                'json' => $body,
                'http_errors' => false
            ]
        );

        /**
         * Лог ответа
         */
        Log::info('unione.response', [
            'request' => ['uri' => $uri,'body' => $bodyLog],
            'response' => [json_decode($response->getBody(), true)]
        ]);

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
