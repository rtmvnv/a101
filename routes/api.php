<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/mailru', function (Request $request) {
    echo 'test mailru callback GET' . PHP_EOL . $request;
    Log::debug('GET request from Mailru' . PHP_EOL . $request);
});

Route::post('/mailru', function (Request $request) {
    Log::debug('POST request from Mailru' . PHP_EOL . $request);
    echo 'test mailru callback POST';
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
