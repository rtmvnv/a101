<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\Accrual;
use App\MoneyMailRu;

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
    parse_str('data=eyJib2R5Ijp7Im5vdGlmeV90eXBlIjoiVFJBTlNBQ1RJT05fU1RBVFVTIiwiaXNzdWVyX2lkIjoiZDZlNjRmODItZGJjNi00ZTg1LThmODctZTEzMjNmNWJmNzdhIiwic3RhdHVzIjoiUEFJRCIsImFkZGVkIjoiMjAyMS0wOS0wOFQxNjoyOTowNS4wMDArMDM6MDAiLCJ0eG5faWQiOiIxMDMzMjIzMzk4ODQzNzQ5MDQyOCIsInVzZXJfaW5mbyI6eyJ1c2VyX2lkIjoiNmRmM2U0YmYtYmE2Yy00Y2VmLTk5NWEtYjk4YTU1YzJkOWYxIn0sImN1cnJlbmN5IjoiUlVCIiwia2VlcF91bmlxIjoiMCIsInBheV9zeXN0ZW1fbmFtZSI6ItCR0LDQvdC%2B0LLRgdC60LjQtSDQutCw0YDRgtGLICjQotCV0KHQoikiLCJwYXllZV9mZWVfYW1vdW50IjoiNDMuMzciLCJwYXllZV9hbW91bnQiOiI4MjMuOTkiLCJwYXlfbWV0aG9kIjoiY3BndGVzdCIsIm1lcmNoYW50X2lkIjoiMjUyNTIwIiwiZGVzY3JpcHRpb24iOiLQntC%2F0LvQsNGC0LAg0LrQstC40YLQsNC90YbQuNC4IEExMDEg0L%2FQviDQu9C40YbQtdCy0L7QvNGDINGB0YfQtdGC0YMge3sg0JHQkjc4NTU1OSB9fSDQt9CwIHt7INGB0LXQvdGC0Y%2FQsdGA0YwgMjAyMSB9fSIsIm1lcmNoYW50X25hbWUiOiLQkDEwMSDQmtC%2B0LzRhNC%2B0YDRgiIsIm1lcmNoYW50X3BhcmFtIjp7fSwicGFpZCI6IjIwMjEtMDktMDhUMTY6Mjk6MjYuMDAwKzAzOjAwIiwiYW1vdW50IjoiODY3LjM2IiwidHJhbnNhY3Rpb25faWQiOiJCRDVCQkY4MC0xMEE4LTExRUMtODY2OC0xRjQzN0QwRjdEN0QifSwiaGVhZGVyIjp7InN0YXR1cyI6Ik9LIiwidHMiOiIxNjMxMTA3NzY3IiwiY2xpZW50X2lkIjoiMjUyNTIwIiwiZXJyb3IiOnsiZGV0YWlscyI6e319fX0%3D&signature=k%2BoRXgO5H1Tg238PtY0q%2FFpurroqqau9cZYF9zmrDlaT4E%2BQW0pvDDFkthhOH8PJqxgt615d7hxBSlinL0JcTIh6AIwT6cI9lmXTa8ZMyntj5ic7RsD%2F0NtQMkVc%2BjMXlpSAx9pVL4tcub153hLXgcHpOJW6bT%2BpjVbZumFx97k62tESL9t86knUSvC1K6wYmSvCBIYVzy4Y7F9ydOvLn8BgewYWg%2FhKc6vb9xsez1tVospF00BJJVqAQIKYy%2BQ8GDcnK3s%2B9k%2BgSfur1YPUQzCuMbabiOGWyHR9wSIKzG3naa1zH%2F0hOxFBBw%2BIhf9VN12xj%2BtN1v3xHF8ucWDkGQ%3D%3D&version=2-03', $request);

    $callback = MoneyMailRu::parseCallback($request);
    print_r($callback);
    $accrual = Accrual::find($callback['body']['issuer_id']);
    print_r($accrual);
});


Route::post('/mailru', function (Request $request) {
    $callback = MoneyMailRu::parseCallback($request);
    $accrual = Accrual::where('transaction_id', $callback['body']['transaction_id'])->firstOrFail();
    Log::debug(print_r($accrual, true));
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
