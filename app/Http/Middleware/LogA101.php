<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogA101
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $api)
    {

        $response = $next($request);

        $mongo = (new \MongoDB\Client(
            'mongodb://' . config('services.mongo.server'),
            [
                'username' => config('services.mongo.username'),
                'password' => config('services.mongo.password'),
                'authSource' => config('services.mongo.auth_source'),
            ],
            [
                'typeMap' => [
                    'array' => 'array',
                    'document' => 'array',
                    'root' => 'array',
                ],
            ],
        ))->a101->events;

        $record = [
            'type' => 'api_log',
            'direction' => 'incoming',
            'api' => $api,
            'request' => [
                'datetime' => new \MongoDB\BSON\UTCDateTime(1000 * LARAVEL_START),
                // 'data' => $request->all(),
            ],
            'response' => [
                'datetime' => new \MongoDB\BSON\UTCDateTime(),
            ],
        ];

        if (!empty($response->exception)) {
            // В ответе пришло исключение
            $record['response']['exception'] = [
                'message' => $response->exception->message,
                'code' => $response->exception->code,
                'file_line' => "{$response->exception->file}:{$response->exception->line}",
            ];
        } else {
            $jsonResponse = json_decode($response, true, 512, JSON_OBJECT_AS_ARRAY);

            if (json_last_error() === JSON_ERROR_NONE) {
                // В ответе пришел JSON
                $record['response']['data'] = $jsonResponse;
            } else {
                // Ответ не структурирован
                $record['response']['data'] = $response->content();
            }
        }

        $mongo->insertOne($record);

        return $response;
    }
}
