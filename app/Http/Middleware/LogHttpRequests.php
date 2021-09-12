<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogHttpRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $name)
    {
        Log::info($name . '.request', ['request' => $request->all()]);
        $response = $next($request);
        Log::info($name . '.response', ['request' => $request->all(), 'response' => $response]);
        return $response;
    }
}
