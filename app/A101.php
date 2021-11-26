<?php

namespace App;

use App\Models\Accrual;
use App\Rules\ValidDate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class A101
{
    /**
     * Обработчик POST запросов к /api/a101/accruals
     *
     * @return Response
     */
    function apiAccrualsPost(Request $request)
    {
        try {
            // Про формат обмена данными JSON API
            // https://jsonapi.org/examples/#error-objects
            // https://datatracker.ietf.org/doc/html/rfc7807
            // https://lakitna.medium.com/understanding-problem-json-adf68e5cf1f8

            /**
             * Валидировать данные запроса
             * https://laravel.su/docs/8.x/validation
             */
            $validator = Validator::make(
                $request->all(),
                [
                    'sum' => 'bail|required|numeric',
                    'period' => 'bail|required|date_format:Ym',
                    'account' => 'bail|required|alpha_num',
                    'email' => 'bail|required|email:rfc',
                    'name' => 'bail|required|string',
                    'signature' => 'bail|required|alpha_dash',
                ]
            );

            if ($validator->stopOnFirstFailure()->fails()) {
                $errors = $validator->errors();
                $data = [
                    'status' => 400,
                    'title' => $errors->first(),
                ];
                return response($data, $data['status'])
                    ->header('Content-Type', 'application/problem+json');
            }

            /**
             * Проверить подпись
             */
            $signature = $request->sum
                . $request->period
                . $request->account
                . $request->email
                . $request->name;

            $signature = base64_encode($signature);
            $signature = $signature . env('A101_SIGNATURE');
            $signature = hash('sha1', $signature);

            if (strcmp($request->signature, $signature) !== 0) {
                $data = [
                    'status' => 401,
                    'title' => 'Wrong signature',
                ];
                return response($data, $data['status'])
                    ->header('Content-Type', 'application/problem+json');
            }
        } catch (\Throwable $th) {
            $data = [
                'status' => 400,
                'title' => 'Error parsing request',
                'description' => $th->getMessage(),
            ];
            return response($data, $data['status'])
                ->header('Content-Type', 'application/problem+json');
        }

        try {
            /**
             * Создать запись о начислении
             */
            $accrual = new Accrual();
            $accrual->uuid = (string) Str::uuid();
            $accrual->sum = $request->sum / 100;
            $accrual->period = $request->period;
            $accrual->account = $request->account;
            $accrual->email = $request->email;
            $accrual->name = $request->name;
            $accrual->comment = '';

            $this->cancelOtherAccruals($accrual);
            $accrual->save();

            file_put_contents(storage_path('file.pdf'), $request->getContent());

            /**
             * Отправить письмо
             */
            $result = $this->sendAccrual($accrual);

            if ($result !== true) {
                $data = [
                    'status' => 503,
                    'title' => 'Error sending email',
                    'description' => $request,
                ];
                return response($data, $data['status'])
                    ->header('Content-Type', 'application/problem+json');
            }

            $data = [
                'status' => 200,
                'title' => 'OK',
                'data' => [
                    'accrual_id' => $accrual->uuid
                ]
            ];

            return response($data, $data['status'])
                ->header('Content-Type', 'application/json');

        } catch (\Throwable $th) {
            $data = [
                'status' => 500,
                'title' => 'General error sending email',
                'description' => $th->getMessage(),
            ];
            return response($data, $data['status'])
                ->header('Content-Type', 'application/problem+json');
        }
    }

    /**
     * Обработчик GET запросов к /api/a101/payments
     *
     * @return Response
     */
    function apiPaymentsGet(Request $request)
    {
        /**
         * Валидировать данные запроса
         * https://laravel.su/docs/8.x/validation
         */
        $validator = Validator::make(
            $request->all(),
            [
                'from' => ['bail', 'required', new ValidDate],
                'to' => ['bail', new ValidDate],
                'signature' => ['bail', 'required', 'alpha_dash'],
            ]
        );

        if ($validator->stopOnFirstFailure()->fails()) {
            $errors = $validator->errors();
            $data = [
                'status' => 400,
                'title' => $errors->first(),
            ];
            return response($data, $data['status'])
                ->header('Content-Type', 'application/problem+json');
        }

        /**
         * Проверить подпись
         */
        $signature = $request->from;
        if (!empty($request->to)) {
            $signature .= $request->to;
        }

        $signature = base64_encode($signature);
        $signature = $signature . env('A101_SIGNATURE');
        $signature = hash('sha1', $signature);

        if (strcmp($request->signature, $signature) !== 0) {
            $data = [
                'status' => 401,
                'title' => 'Wrong signature',
            ];
            return response($data, $data['status'])
                ->header('Content-Type', 'application/problem+json');
        }

        /**
         * Определить дату
         */
        $from = new Carbon($request->get('from'));
        if ($request->has('to')) {
            $to = new Carbon($request->get('to'));
        } else {
            $to = new Carbon();
        }

        /**
         * Получить данные
         */
        $data = [
            'status' => 200,
            'title' => 'OK',
            'data' => [
                'from' => $from->format('c'),
                'to' =>  $to->format('c'),
                'payments' => []
            ]
        ];

        $accruals = Accrual::where('paid_at', '>=', $from)
            ->where('paid_at', '<', $to)
            ->get();

        foreach ($accruals as $accrual) {
            $data['data']['payments'][] = [
                'date' => (new Carbon($accrual->paid_at))->format('c'),
                'accrual_id' => $accrual->uuid,
                'account' => $accrual->account,
                'sum' => $accrual->sum * 100,
            ];
        }

        return response($data, $data['status'])
            ->header('Content-Type', 'application/json');
    }

    /**
     * Для каждого account может быть актуален только один счет на оплату.
     * При поступлении нового счета остальные неоплаченные отменяются
     *
     * @param Accrual $newAccrual Новый счет на оплату
     * @return null
     */
    public function cancelOtherAccruals(Accrual $Accrual)
    {
        $oldAccruals = Accrual::where('account', $Accrual->account)
            ->where('uuid', '<>', $Accrual->uuid)
            ->where('archived_at', null)
            ->where('paid_at', null)
            ->get();
        foreach ($oldAccruals as $oldAccrual) {
            $oldAccrual->archived_at = now();
            $oldAccrual->comment = 'Счет устарел';
            $oldAccrual->save();
        }
    }

    public function sendAccrual(Accrual $accrual)
    {
        //@debug
        $accrual->email = 'null@vic-insurance.ru';

        $plain = view('mail_plain', $accrual->toArray())->render();
        $html = view('mail_html_' . $accrual->estate, $accrual->toArray())->render();

        $message = new UniOneMessage();
        $message->to($accrual->email, $accrual->full_name)
            ->subject("Квитанция по лицевому счету {$accrual->account} за {$accrual->period_text}")
            ->plain($plain)
            ->html($html);

        $result = $message->send();

        if ($result['status'] === 'success') {
            $accrual->sent_at = now();
            $accrual->save();
            Log::info($result);
            return true;
        } else {
            Log::info($result);
            return $result['message'];
        }
    }
}
