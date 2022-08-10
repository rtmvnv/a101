<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

class LogWeb
{
    protected $request;      // Оригинальный запрос
    protected $requestTime;  // (Carbon) Время запроса
    protected $response;     // Ответ на запрос
    protected $responseTime; // Время ответа на запрос
    protected $record;       // Запись лога
    protected $name;         // Название интерфейса

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $name)
    {
        $this->request = $request;
        $this->name = $name;

        $this->requestTime = CarbonImmutable::now();
        $this->response = $next($request);
        $this->responseTime = CarbonImmutable::now();

        $this->render();

        Log::info($this->name, $this->record);

        return $this->response;
    }

    /**
     * Формирует запись лога
     */
    protected function render()
    {
        $this->record = [
            'path' => $this->request->path(),
            'request_method' => $this->request->method(),
            'request' => $this->request->all(),
            'request_time' => $this->requestTime->format('Y-m-d\TH:i:s.uP'),
        ];

        if (!empty($this->response->exception)) {
            // В ответе пришло исключение
            $this->record['response_type'] = 'exception';
            $this->record['response']['exception'] = [
                'message' => $this->response->exception->getMessage(),
                'code' => $this->response->exception->getCode(),
                'file' => $this->response->exception->getFile(),
                'line' => $this->response->exception->getLine(),
                'trace' => $this->response->exception->getTrace(),
            ];
        } else {
            $jsonResponse = json_decode($this->response->content(), true, 512, JSON_OBJECT_AS_ARRAY);
            if (json_last_error() === JSON_ERROR_NONE) {
                // В ответе пришел JSON
                $this->record['response_type'] = 'json';
                $this->record['response'] = $jsonResponse;
            } else {
                // Ответ неструктурирован
                $this->record['response_type'] = 'raw';
                $this->record['response'] = str_replace(array("\r", "\n"), "", $this->response->headers);
            }

            $this->record['response_time'] = $this->responseTime->format('Y-m-d\TH:i:s.uP');
            $this->record['elapsed'] = $this->responseTime->floatDiffInSeconds($this->requestTime);
        }
    }
}
