<?php

use App\Models\Accrual;
use App\MoneyMailRu;
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
    $data = 'eyJib2R5Ijp7ImN1cnJlbmN5IjoiUlVCIiwiaXNzdWVyX2lkIjoiMTIzX3Rlc3RfYmFyeXNobmlrb3YiLCJhbW91bnQiOiIxMCIsInBheV9tZXRob2QiOiJjcGd0ZXN0IiwidXNlcl9pbmZvIjp7InVzZXJfaWQiOiI0NTQzMjU0MzUzNDUyMzQifSwiZGVzY3JpcHRpb24iOiJ0ZXN0X2JhcnlzaG5pa292In0sImhlYWRlciI6eyJ0cyI6MTYxODMwMDIyNiwiY2xpZW50X2lkIjoyNTI1MjB9fQ%3D%3D';

    print_r(base64_decode($data));
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
            $response = $mailru->startTransaction(
                userId: $accrual->account,
                amount: $accrual->sum,
                description: "Оплата квитанции A101 по лицевому счету {{ $accrual->account_name }} за {{ $accrual->period_text }}",
                backUrl: url('/') . '/' . $accrual->uuid . '/pay',
                successUrl: url('/') . '/' . $accrual->uuid . '/completed',
                failUrl: url('/') . '/' . $accrual->uuid . '/failed',
            );

            Log::debug($response);

            // Временная ошибка
            if ($response['result_code'] !== 0) {
                $accrual->failed_comment = 'Система временно недоступна. Повторите запрос позже.';
                $accrual->save();
                return view('failed', $accrual->toArray());
            }

            // Постоянная ошибка
            if ($response['header']['status'] !== 'OK') {
                $accrual->failed_at = now();
                $accrual->failed_comment = 'При обращении в банк произошла ошибка, для оплаты квитанции обратитесь в службу поддержки.';
                $accrual->save();
                return view('failed', $accrual->toArray());
            }

            $accrual->confirmed_at = now();
            $accrual->link_bank = $response['body']['action_param']['url'];
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
