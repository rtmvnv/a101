<?php

namespace App;

use App\Models\Accrual;
use App\Rules\ValidDate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\UniOne\UniOne;
use App\UniOne\Message;
use App\MoneyMailRu\Callback;
use App\XlsxToPdf;

class A101
{
    /**
     * Обработчик POST запросов к /api/unione
     *
     * @return Response
     */
    public function postApiUnione(Request $request)
    {
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
        $accrual->unione_at = now();
        $accrual->save();
    }

    /**
     * Обработчик POST запросов к /api/mailru
     *
     * @return Response
     */
    public function postApiMailru(Request $request)
    {
        // Прочитать колбек
        try {
            $callback = new Callback($request);
        } catch (\Throwable $th) {
            report($th);
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

            // Отправить абоненту письмо с подтверждением
            $this->sendConfirmation($accrual);
        } else {
            // Транзакция отменена
            $accrual->comment = 'Оплата не прошла. ' . ($callback->body['decline_reason']) ?? 'unknown';
            $accrual->save();
        }

        return $callback->respondOk();
    }

    public function postApiAccrualsSignature(array $data, $verbose = false)
    {
        $signature = $data['sum']
            . $data['period']
            . $data['account']
            . $data['email']
            . $data['name'];
        if ($verbose) {
            echo '1 concatenate: ' . $signature . PHP_EOL;
        }

        $signature = base64_encode($signature);
        if ($verbose) {
            echo '2 base64_encode(): ' . $signature . PHP_EOL;
        }
        $signature = $signature . env('A101_SIGNATURE');
        if ($verbose) {
            echo '3. add key: ' . $signature . PHP_EOL;
        }
        $signature = hash('sha1', $signature);
        if ($verbose) {
            echo '4. sha1(): ' . $signature . PHP_EOL . PHP_EOL;
        }

        return $signature;
    }

    /**
     * Обработчик POST запросов к /api/a101/accruals
     *
     * @return Response
     */
    public function postApiAccruals(Request $request, $payee)
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
                    'account' => 'bail|required|alpha_dash',
                    'name' => 'bail|required|string',
                    'email' => 'bail|required|string',
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
             * Проверить корректность email
             */
            $request->email = mb_strtolower($request->email);
            Accrual::parseEmail($request->email);

            /**
             * Проверить подпись
             */
            $signature = $this->postApiAccrualsSignature($request->all());

            if (strcmp($request->signature, $signature) !== 0) {
                $data = [
                    'status' => 401,
                    'title' => 'Wrong signature',
                ];
                return response($data, $data['status'])
                    ->header('Content-Type', 'application/problem+json');
            }

            /**
             * Проверить приложение
             */
            $attachment = $request->getContent();

            if (empty($attachment)) {
                $data = [
                    'status' => 401,
                    'title' => 'Empty attachment',
                ];
                return response($data, $data['status'])
                    ->header('Content-Type', 'application/problem+json');
            }

            // Убрать лишние переносы строк для дальнейшего сравнения
            $attachment = str_replace(array("\r", "\n"), '', $attachment);
            $attachment = base64_decode($attachment, true);

            if ($attachment === false) {
                $data = [
                    'status' => 401,
                    'title' => 'Attachment is not encoded in base64',
                ];
                return response($data, $data['status'])
                    ->header('Content-Type', 'application/problem+json');
            }

            // Сконвертировать в PDF, если прислали XLSX
            $attachment = app(XlsxToPdf::class)($attachment);
            $attachment = base64_encode($attachment);

        } catch (\Throwable $th) {
            report($th);
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

            if ($accrual->sum <= 0) {
                $accrual->archived_at = now();
                $accrual->comment = 'Баланс положительный, оплата не требуется';
            }

            $accrual->payee = $payee;
            if ($accrual->payee == 'etk2') {
                $accrual->archived_at = now();
                $accrual->comment = 'Счет ЭТК2, оплата не принимается';
            }

            $this->cancelOtherAccruals($accrual);
            $accrual->save();

            /**
             * Отправить письмо
             */
            $result = $this->sendAccrual($accrual, $attachment);

            if ($result !== true) {
                $data = [
                    'status' => 503,
                    'title' => 'Error sending email',
                    'description' => $result,
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
            report($th);
            $accrual->archived_at = now();
            $accrual->comment = 'General error sending email';
            $accrual->save();
            $data = [
                'status' => 500,
                'title' => 'General error sending email',
                'description' => $th->getMessage(),
            ];
            return response($data, $data['status'])
                ->header('Content-Type', 'application/problem+json');
        }
    }

    public function getApiPaymentsSignature(array $data)
    {
        $signature = $data['from'];
        if (!empty($data['to'])) {
            $signature .= $data['to'];
        }

        $signature = base64_encode($signature);
        $signature = $signature . env('A101_SIGNATURE');
        $signature = hash('sha1', $signature);

        return $signature;
    }

    /**
     * Обработчик GET запросов к /api/a101/payments
     *
     * @return Response
     */
    public function getApiPayments(Request $request)
    {
        /**
         * Валидировать данные запроса
         * https://laravel.su/docs/8.x/validation
         */
        $validator = Validator::make(
            $request->all(),
            [
                'from' => ['bail', 'required', new ValidDate()],
                'to' => ['bail', new ValidDate()],
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
        $signature = $this->getApiPaymentsSignature($request->all());

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
                'payee' => $accrual->payee,
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
            ->get();
        foreach ($oldAccruals as $oldAccrual) {
            $oldAccrual->archived_at = now();
            $oldAccrual->comment = 'Счет устарел';
            $oldAccrual->save();
        }
    }

    public function sendAccrual(Accrual $accrual, $attachment)
    {
        $message = app(Message::class);

        if ($accrual->payee == 'a101') {
            $plain = view('mail_a101/plain', $accrual->toArray())->render();
            $html = view('mail_a101/html', $accrual->toArray())->render();

            $message->addInlineAttachment(
                'image/png',
                'a101-comfort.png',
                base64_encode(file_get_contents(public_path('images/a101-comfort.png')))
            );

            $message->from(config('services.from.a101.email'), config('services.from.a101.name'));

            $message->addAttachment(
                'application/pdf',
                'Оплачивайте ЖКУ в мобильном приложении А101.pdf',
                base64_encode(file_get_contents(storage_path('a101_second_attachment.pdf'))),
            );
        } elseif ($accrual->payee == 'etk2') {
            $plain = view('mail_etk2/plain', $accrual->toArray())->render();
            $html = view('mail_etk2/html', $accrual->toArray())->render();

            $message->addInlineAttachment(
                'image/png',
                'etk2.png',
                base64_encode(file_get_contents(public_path('images/etk2.png')))
            );

            $message->from(config('services.from.etk2.email'), config('services.from.etk2.name'));
        } else {
            throw new \Exception("Unknown payee: '{$accrual->payee}'", 44814051);
        }

        $message->to($accrual->email, $accrual->name)
            ->subject("Квитанция по лицевому счету {$accrual->account} за {$accrual->period_text}")
            ->plain($plain)
            ->html($html)
            ->addAttachment(
                'application/pdf',
                "Квитанция по ЛС {$accrual->account} за {$accrual->period_text}.pdf",
                $attachment
            )
            ->addInlineAttachment(
                'image/jpg',
                'estate',
                base64_encode(file_get_contents(public_path('images/' . $accrual->estate . '.jpg')))
            );

        $unione = app(UniOne::class);
        $result = $unione->emailSend($message);

        if ($result['status'] === 'success') {
            $accrual->unione_id = $result['job_id'];
            $accrual->sent_at = now();
            $accrual->save();
            return true;
        } else {
            $accrual->archived_at = now();
            $accrual->comment = 'Error sending email';
            $accrual->save();
            return $result['message'];
        }
    }

    public function sendConfirmation(Accrual $accrual)
    {
        $message = new Message();

        if ($accrual->payee == 'a101') {
            $plain = view('mail_a101/plain_confirmation', $accrual->toArray())->render();
            $html = view('mail_a101/html_confirmation', $accrual->toArray())->render();

            $message->addInlineAttachment(
                'image/png',
                'a101-comfort.png',
                base64_encode(file_get_contents(public_path('images/a101-comfort.png')))
            );

            $message->from(config('services.from.a101.email'), config('services.from.a101.name'));
        } elseif ($accrual->payee == 'etk2') {
            $plain = view('mail_etk2/plain_confirmation', $accrual->toArray())->render();
            $html = view('mail_etk2/html_confirmmation', $accrual->toArray())->render();

            $message->addInlineAttachment(
                'image/png',
                'etk2.png',
                base64_encode(file_get_contents(public_path('images/etk2.png')))
            );

            $message->from(config('services.from.etk2.email'), config('services.from.etk2.name'));
        } else {
            throw new \Exception("Unknown payee: '{$accrual->payee}'", 44814051);
        }

        $message->to($accrual->email, $accrual->name)
            ->subject("Оплачена квитанция по лицевому счету {$accrual->account} за {$accrual->period_text}")
            ->plain($plain)
            ->html($html)
            ->addInlineAttachment(
                'image/jpg',
                'estate',
                base64_encode(file_get_contents(public_path('images/' . $accrual->estate . '.jpg')))
            );

        $unione = app(UniOne::class);
        $unione->emailSend($message);
    }
}
