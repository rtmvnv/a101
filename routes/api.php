<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Accrual;
use App\MoneyMailRu\MoneyMailRu;
use App\MoneyMailRu\Callback;
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

Route::post('/a101/accruals', [A101::class, 'apiAccrualsPost'])
    ->middleware('log.api:incoming-api-accruals');
    // ->middleware('log.mongo:a101');

Route::get('/a101/payments', [A101::class, 'apiPaymentsGet'])
    ->middleware('log.api:incoming-api-payments');

Route::post('/unione', function (Request $request) {
    /**
     * https://docs.unione.ru/web-api-ref#callback-format
     */
    if ($request->event_name !== 'transactional_email_status') {
        return;
    }

    // Найти транзакцию
    $accrual = Accrual::where('unione_id', $request->job_id)->first();
    if (empty($accrual)) {
        return;
    }

    $accrual->unione_status = $request->status;
    $accrual->save();
})->middleware('log.api:incoming-unione');

Route::post('/mailru', function (Request $request) {
    // Прочитать колбек
    try {
        $callback = new Callback($request);
    } catch (\Throwable $th) {
        return Callback::respondFatal($th->getMessage());
    }

    // Проверить корректность
    $validationResult = $callback->validate();
    if ($validationResult !== true) {
        return $validationResult;
    }

    // Найти транзакцию
    $accrual = Accrual::where('transaction_id', $callback->body['transaction_id'])->first();
    if (empty($accrual)) {
        return $callback->respondError('Transaction not found: ' . $callback->body['transaction_id']);
    }

    $accrual->callback_data = base64_decode($request['data'], true);
    $accrual->save();

    // Принимаемое значение: (new|rejected|paid|expired|held|hold_failed|hold_canceled)
    if ($callback->body['status'] === 'PAID') {
        // Успешная транзакция
        if ($accrual->paid_at !== null) {
            // Уже был колбек об успешном завершении транзакции
            Log::notice(
                'MoneyMailRu прислал колбек OK для уже завершенной транзакции',
                ['header' => $callback->header, 'body' => $callback->body]
            );
            return $callback->respondError('Transaction already completed: ' . $callback->body['transaction_id'], 'ERR_DUPLICATE');
        }
        $accrual->paid_at = now();
        $accrual->archived_at = now();
        $accrual->save();
    } else {
        // Транзакция отменена
        $accrual->comment = 'Оплата не прошла. ' . ($callback->body['decline_reason']) ?? 'unknown';
        $accrual->save();
    }

    return $callback->respondOk();
})
    ->middleware('log.mailru:incoming-api-mailru');

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
