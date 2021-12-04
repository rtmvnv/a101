<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogMailru
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $mongo = new \MongoDB\Client(
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
        );

        // Log::info('request', ['request' => $request->all()]);
        $response = $next($request);
        // Log::info(
        //     $name . '.response',
        //     [
        //         'request' => $request->all(),
        //         'response' => str_replace(array("\r","\n"), "", $response)
        //     ]
        // );
        return $response;
    }
}
