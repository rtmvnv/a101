<?php

use Illuminate\Http\Request;
use App\Models\Accrual;
use App\MoneyMailRu\MoneyMailRu;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\LoginController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/dev', function () {
    if (App::environment('production')) {
        abort(404);
    }
});

Route::get('/internal/login', [LoginController::class, 'create'])->middleware('guest')->name('login');
Route::post('/internal/login', [LoginController::class, 'store'])->middleware('guest');
Route::post('/internal/logout', [LoginController::class, 'destroy'])->middleware('auth');

Route::get('/internal/dashboard', [Dashboard::class, 'show'])->middleware('auth');

// Найти запись по полю uuid и вернуть в переменной accrual
Route::get('/accrual/{accrual:uuid}', function (Accrual $accrual) {
    switch ($accrual->status) {
        case 'sent': // Клиент первый раз перешел по ссылке из письма
            $accrual->opened_at = now();
            $accrual->save();
            return view('confirm', $accrual->toArray());
            break;

        case 'opened': // Клиент перезагрузил страницу
        case 'confirmed': // Клиент еще раз перешел из письма
            return view('confirm', $accrual->toArray());
            break;

        case 'paid':
            return view('paid', $accrual->toArray());
            break;

        case 'failed':
            return view('failed', $accrual->toArray());
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "sent"');
            break;
    }
})->whereUuid('accrual')->middleware('log.web:incoming-web-accrual');

Route::get('/accrual/{accrual:uuid}/pay', function (Accrual $accrual) {
    switch ($accrual->status) {
        case 'sent':
            $accrual->opened_at = now();
            $accrual->save();
            return view('confirm', $accrual->toArray());
            break;

        case 'opened':
        case 'confirmed':
            $mailru = app(MoneyMailRu::class);
            $response = $mailru->transactionStart(
                issuerId: $accrual->uuid,
                userId: base64_encode($accrual->account), // Mail.ru не принимает кириллицу в этом поле
                amount: $accrual->sum,
                description: "Оплата квитанции A101 по лицевому счету {{ $accrual->account }} за {{ $accrual->period_text }}",
                backUrl: $accrual->link_back,
            );

            // Ошибка при осуществлении запроса
            if ($response['result_code'] !== 0) {
                $accrual->comment = 'Система временно недоступна. Повторите запрос позже.';
                $accrual->save();
                return view('failed', $accrual->toArray());
            }

            // Mailru вернул ошибку
            if ($response['header']['status'] !== 'OK') {
                $accrual->comment = 'При обращении в банк произошла ошибка, для оплаты квитанции обратитесь в службу поддержки.';
                $accrual->save();
                return view('failed', $accrual->toArray());
            }

            $accrual->confirmed_at = now();
            $accrual->url_bank = $response['body']['action_param']['url'];
            $accrual->transaction_id = $response['body']['transaction_id'];
            $accrual->save();
            return view('pay', $accrual->toArray());
            break;

        case 'paid':
            return view('paid', $accrual->toArray());
            break;

        case 'failed':
            return view('failed', $accrual->toArray());
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "opened"');
            break;
    }
})->whereUuid('accrual')->middleware('log.web:incoming-web-pay');

Route::get('/accrual/{accrual:uuid}/back', function (Accrual $accrual, Request $request) {
    switch ($accrual->status) {
        case 'confirmed':
        case 'paid':
        case 'failed':
            if ($request->input('status') === 'fail') {
                return view('failed', $accrual->toArray());
            }

            if ($request->input('status') !== 'success') {
                throw new ModelNotFoundException('status is not "success"');
            }

            $paymentInfo = json_decode(
                base64_decode($request->input('payment_info')),
                JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            $accrual->back_data = base64_decode($request->input('payment_info'));
            $accrual->save();

            if ($request->input('issuer_id') === $accrual->uuid) {
                return view('paid', $accrual->toArray());
            } else {
                return view('failed', $accrual->toArray());
            }
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "confirmed"');
            break;
    }
})->whereUuid('accrual')->middleware('log.web:incoming-web-back');
