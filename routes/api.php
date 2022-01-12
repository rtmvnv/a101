<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\A101;

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

Route::middleware('throttle:600,1')
    ->post('/a101/accruals', function (Request $request) {
        $a101 = new A101();
        return $a101->postApiAccruals($request, 'a101');
    })
    ->middleware('log.api:incoming-api-accruals');

Route::middleware('throttle:600,1')
    ->post('/a101/etk2', function (Request $request) {
        $a101 = new A101();
        return $a101->postApiAccruals($request, 'etk2');
    })
    ->middleware('log.api:incoming-api-etk2');

Route::middleware('throttle:60,1')
    ->get('/a101/payments', [A101::class, 'getApiPayments'])
    ->middleware('log.api:incoming-api-payments');

Route::middleware('throttle:600,1')
    ->post('/unione', [A101::class, 'postApiUnione'])
    ->middleware('log.api:incoming-api-unione');

Route::middleware('throttle:600,1')
    ->post('/mailru', [A101::class, 'postApiMailru'])
    ->middleware('log.mailru:incoming-api-mailru');

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
