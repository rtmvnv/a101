<?php

namespace App;

use App\Models\Accrual;
use Illuminate\Support\Str;

class A101
{
    /**
     * Получить и записать в БД счета за прошлый месяц.
     * Счета без email не пропускаются.
     *
     * @return int Количество записанных счетов
     */
    public function receiveAccruals(): int
    {
        $period = (new \DateTime('last day of last month'))->format('Ym');

        $json = file_get_contents(storage_path('app/A101/GetAllAccrualsFromPeriodExample3.json'));

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

            if (empty($fields['Email'])) continue;

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
            } else {
                $accruals[] = $accrual;
            }

            $countAccruals++;
        }

        if ($updateDb) {
            return $countAccruals;
        } else {
            return $accruals;
        }
    }
}
