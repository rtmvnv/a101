<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogMailru extends LogApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    protected function render()
    {
        parent::render();

        if (!isset($this->record['request']['data'])) {
            $this->record['request']['data'] = '';
        }
        $json = base64_decode($this->record['request']['data'], true);
        if ($json !== false) {
            $array = json_decode($json, true);
            if (!$array) {
                $this->record['request']['json'] = $json;
            } else {
                $this->record['request_decoded'] = $array;
            }
        }

        if ($this->record['response_type'] === 'json') {
            $json = base64_decode($this->record['response']['data'], true);
            if ($json !== false) {
                $array = json_decode($json, true);
                if (!$array) {
                    $this->record['response']['json'] = $json;
                } else {
                    $this->record['response_decoded'] = $array;
                }
            }
        }
    }
}
