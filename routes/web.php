<?php

use Illuminate\Http\Request;
use App\Models\Accrual;
use App\MoneyMailRu\MoneyMailRu;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

Route::get('/', function (Request $request) {

    echo (string) Str::uuid();

    $string = '123400202112ИК12345678user@example.comПупкин В.А.';

    $string1 = 'MTIzNDAwMjAyMTEy0JjQmjEyMzQ1Njc4dXNlckBleGFtcGxlLmNvbdCf0YPQv9C60LjQvSDQki7QkC4=b3ff7d3e-fb87-47f2-84a0-5b75a7b115d9';

    echo sha1($string1);
});


// Найти запись по полю uuid и вернуть в переменной accrual
Route::get('/{accrual:uuid}', function (Accrual $accrual) {
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

        case 'payed':
            return view('payed', $accrual->toArray());
            break;

        case 'failed':
            return view('failed', $accrual->toArray());
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "sent"');
            break;
    }
})->whereUuid('accrual');

Route::get('/{accrual:uuid}/pay', function (Accrual $accrual) {
    switch ($accrual->status) {
        case 'sent':
            $accrual->opened_at = now();
            $accrual->save();
            return view('confirm', $accrual->toArray());
            break;

        case 'opened':
            $mailru = app(MoneyMailRu::class);
            $response = $mailru->transactionStart(
                issuerId: $accrual->uuid,
                userId: $accrual->account,
                amount: $accrual->sum,
                description: "Оплата квитанции A101 по лицевому счету {{ $accrual->account_name }} за {{ $accrual->period_text }}",
                backUrl: url('/') . '/' . $accrual->uuid . '/back',
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

        case 'confirmed':
            return view('pay', $accrual->toArray());
            break;

        case 'payed':
            return view('payed', $accrual->toArray());
            break;

        case 'failed':
            return view('failed', $accrual->toArray());
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "opened"');
            break;
    }
})->whereUuid('accrual');

Route::get('/{accrual:uuid}/back', function (Accrual $accrual, Request $request) {

    switch ($accrual->status) {
        case 'confirmed':
        case 'payed':
        case 'failed':
            if ($request->input('status') !== 'success') {
                throw new ModelNotFoundException('status is not "success"');
            }

            $paymentInfo = json_decode(
                base64_decode($request->input('payment_info')),
                JSON_OBJECT_AS_ARRAY | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            Log::info('mailru.back_url.process', [
                'uuid' => $accrual->uuid,
                'status' => $request->input('status'),
                'payment_info' => $paymentInfo
            ]);

            $accrual->back_data = base64_decode($request->input('payment_info'));
            $accrual->save();

            if ($request->input('issuer_id') === $accrual->uuid) {
                return view('payed', $accrual->toArray());
            } else {
                return view('failed', $accrual->toArray());
            }
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "confirmed"');
            break;
    }
})->whereUuid('accrual')->middleware('log.web:mailru.back_url');
