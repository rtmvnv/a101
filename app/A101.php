<?php

namespace App;

use App\Models\Accrual;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class A101
{
    /**
     * Обработчик POST запросов к /api/a101/accruals
     *
     * @return Response 
     */
    function apiAccrualsPost(Request $request) {
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
                'sum' => 'required|numeric',
                'period' => 'required|date_format:Ym',
                'account' => 'required|alpha_num',
                'email' => 'required|email:rfc',
                'name' => 'required|string',
                'signature' => 'required|alpha_dash',
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
    
        $data = [];
        $data['status'] = 500;
        $data['title'] = 'Work in progress';
    
        return response($data, $data['status'])
            ->header('Content-Type', ((int)$data['status'] == 200) ? 'application/json' : 'application/problem+json');
    }

    /**
     * Обработчик GET запросов к /api/a101/payments
     *
     * @return Response 
     */
    function apiPaymentsGet(Request $request) {
        /**
         * Валидировать данные запроса
         * https://laravel.su/docs/8.x/validation
         */

        print_r($request->all());

        $validator = Validator::make(
            $request->all(),
            [
                'from' => 'required',
                'to' => 'date_format:c',
                'signature' => 'required|alpha_dash',
            ]
        );
    
        if ($validator->stopOnFirstFailure()->fails()) {
            $errors = $validator->errors();
            $data = [
                'status' => 400,
                'title' => $errors->first(),
            ];
            print_r($data);
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
    
        $data = [];
        $data['status'] = 500;
        $data['title'] = 'Work in progress';
    
        return response($data, $data['status'])
            ->header('Content-Type', ((int)$data['status'] == 200) ? 'application/json' : 'application/problem+json');
    }

    /**
     * Получить и записать в БД счета за прошлый месяц.
     * Счета без email не пропускаются.
     *
     * @return int Количество записанных счетов
     */
    public function receiveAccruals(): int
    {
        $period = (new \DateTime('last day of last month'))->format('Ym');

        $json = file_get_contents(storage_path('app/A101/GetAllAccrualsFromPeriod.json'));

        return $this->parseJson($json, $period, updateDb: true);
    }

    /**
     * Получить pdf файл с начислениями по одному лицевому счету.
     *
     * @param string $account Лицевой счет
     * @param string $period  Месяц формате "ГодМесяц", пример: "202105"
     * @return mixed
     */
    public function getAccrualFromPeriodPDF($account, $period)
    {
        // $results = Http::get($url)->json();
    }

    /**
     * Получить данные с начислениями по одному лицевому счету.
     *
     * @param string $account Лицевой счет
     * @param string $period  Месяц формате "ГодМесяц", пример: "202105"
     * @return Accrual
     */
    public function getAccrualFromPeriod($account, $period): Accrual
    {
        $json = file_get_contents(storage_path('app/A101/GetAccrualFromPeriodExample.json'));
        return $this->parseJson($json, $period)[0];
    }

    /**
     * Получить данные с начислениями по всем лицевым счетам
     * (по которым есть начисления в выбранном периоде).
     *
     * @param string $org     Организация
     * @param string $period  Месяц формате "ГодМесяц", пример: "202105"
     * @return array
     */
    public function getAllAccrualsFromPeriod($period): array
    {
        $json = file_get_contents(storage_path('app/A101/GetAllAccrualsFromPeriodExample.json'));
        return $this->parseJson($json, $period);
    }

    protected function parseJson($json, $period, $updateDb = false)
    {
        $array = json_decode($json, true, 10, JSON_THROW_ON_ERROR);

        $columns = [];
        foreach ($array['#value']['column'] as $key => $value) {
            $columns[$key] = $value['Name'];
        }

        $accruals = [];
        $countAccruals = 0;
        foreach ($array['#value']['row'] as $row) {
            $fields = [];
            foreach ($row as $key => $value) {
                if (!empty($value)) {
                    $fields[$columns[$key]] = $value['#value'];
                } else {
                    $fields[$columns[$key]] = null;
                }
            }

            if (empty($fields['Email'])) {
                continue;
            }

            $accrual = new Accrual();
            $accrual->uuid = (string) Str::uuid();
            $accrual->period = $period;
            $accrual->person = $fields['ФизическоеЛицо'];
            $accrual->full_name = $fields['ФИО'];
            $accrual->email = $fields['Email'];
            $accrual->account = $fields['ЛицевойСчет'];
            $accrual->account_name = $fields['ЛС'];
            $accrual->sum = $fields['СуммаОплаты'];
            $accrual->sum_accrual = $fields['СуммаНачисления'];
            $accrual->sum_advance = $fields['Аванс'];
            $accrual->sum_debt = $fields['СуммаДолга'];
            $accrual->org = $fields['Организация'];
            $accrual->org_name = $fields['ОрганизацияНаименование'];
            $accrual->org_account = $fields['НаименованиеБанковскогоСчетаУК'];
            $accrual->date_a101 = $fields['ДатаВыгрузки'];
            $accrual->comment = '';

            if ($updateDb) {
                $accrual->save();
                $this->cancelPreviousAccruals($accrual);
            } else {
                $accruals[] = $accrual;
            }

            $countAccruals++;

            /**
             * Отменить счет за предыдущий период
             */
        }

        if ($updateDb) {
            return $countAccruals;
        } else {
            return $accruals;
        }
    }

    /**
     * Для каждого account может быть актуален только один счет на оплату.
     * При поступлении нового счета старые неоплаченные отменяются
     *
     * @param Accrual $newAccrual Новый счет на оплату
     * @return null
     */
    public function cancelPreviousAccruals(Accrual $newAccrual)
    {
        $oldAccruals = Accrual::where('account', $newAccrual->account)
            ->where('archived_at', null)
            ->where('period', '<>', $newAccrual->period)
            ->get();
        foreach ($oldAccruals as $oldAccrual) {
            $oldAccrual->archived_at = now();
            $oldAccrual->comment = 'Счет устарел';
            $oldAccrual->save();
        }
    }
}
