<?php

use Illuminate\Http\Request;
use App\Models\Accrual;
use App\MoneyMailRu\MoneyMailRu;
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

Route::get('/', function (Request $request) {
    $p = 'eyJtZXJjaGFudF9uYW1lIjoiVGVzdFNob3BzIiwiaW50eXBlIjoiT1JEUEFZIiwiYW1vdW50IjoiODczLjA2IFJVQiIsInRyYW5zYWN0aW9uX2lkIjoiMTA2NDI2NTIxNTQ5NTE4OTA5NDIiLCJjb21wYW55Ijoi0KDQndCa0J4gwqvQlNC10L3RjNCz0Lgu0JzRjdC50Lsu0KDRg8K7ICjQntCe0J4pIiwib3BlcmF0aW9uX3R5cGUiOiLQntC/0LvQsNGC0LAiLCJkYXRlIjoiMjAyMS0wOS0xNCAxMzowMiJ9';
    $p = json_decode(base64_decode($p), JSON_OBJECT_AS_ARRAY);
    print_r($p);
    print_r(json_encode($p, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
});


// Найти запись по полю uuid и вернуть в переменной accrual
Route::get('/{accrual:uuid}', function (Accrual $accrual) {
    switch ($accrual->status) {
        case 'sent': // Клиент первый раз перешел по ссылке из письма
        case 'opened': // Клиент перезагрузил страницу
        case 'confirmed': // Клиент еще раз перешел из письма
            $accrual->opened_at = now();
            $accrual->save();
            return view('confirm', $accrual->toArray());
            break;

        case 'completed':
            return view('completed', $accrual->toArray());
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

            // Временная ошибка
            if ($response['result_code'] !== 0) {
                $accrual->comment = 'Система временно недоступна. Повторите запрос позже.';
                $accrual->save();
                return view('failed', $accrual->toArray());
            }

            // Постоянная ошибка
            if ($response['header']['status'] !== 'OK') {
                $accrual->failed_at = now();
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

        case 'completed':
            return view('completed', $accrual->toArray());
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
        case 'completed':
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
                return view('completed', $accrual->toArray());
            } else {
                return view('failed', $accrual->toArray());
            }
            break;

        default:
            throw new ModelNotFoundException('Expected accrual status "confirmed"');
            break;
    }
})->whereUuid('accrual')->middleware('log.web:mailru.back_url');
