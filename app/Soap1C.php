<?php

namespace App;

use SoapClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Soap1C
{
    protected $tempDirectory;

    function __construct() {
        $this->tempDirectory = storage_path('soap1c');
    }

    /**
     * Получить данные с начислениями по всем лицевым счетам
     * (по которым есть начисления в выбранном периоде)
     *
     * @param string $period Месяц начислений в любом формате
     * @return array
     */
    public function getAllAccrualsFromPeriod($period): array
    {
        $client = new SoapClient(
            env('A101_WSDL'),
            [
                'login' => env('A101_USERNAME'), // логин пользователя к базе 1С
                'password' => env('A101_PASSWORD'), // пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, // версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true
            ]
        );

        $carbon = new Carbon($period);
        $result = $client->GetAllAccrualsFromPeriod([
            'Period' => $carbon->format('Y-m-01'),
            'Org' => env('A101_ORG')
        ]);
        // $result->return содержит строку в формате JSON

        Log::info("Soap1C request: " . $client->__getLastRequest());
        Log::info("Soap1C response: " . $client->__getLastResponse());

        return $this->parseJson($result->return);
    }

    /**
     * Получить pdf файл с начислениями по одному лицевому счету
     *
     * @param string $account Лицевой счет
     * @param string $period  Месяц в любом формате
     * @return string Имя PDF файла
     */
    public function getAccrualFromPeriodPDF($period, $account, $person)
    {
        $client = new SoapClient(
            env('A101_WSDL'),
            [
                'login' => env('A101_USERNAME'), // логин пользователя к базе 1С
                'password' => env('A101_PASSWORD'), // пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, // версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true
            ]
        );

        // Сделать запрос к 1С
        $carbon = new Carbon($period);
        $result = $client->getAccrualFromPeriodPDF([
            'Period' => $carbon->format('Y-m-01'),
            'Org' => env('A101_ORG'),
            'Account' => $account,
            'Person' => $person,
        ]);
        if (empty($result->return)) {
            throw new Exception('Got empty PDF file from Soap1C', 92289618);
        }

        // Сделать директорию для временных файлов
        if (!file_exists($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0777, true);
        }

        // Записать файл
        $fileName = $this->tempDirectory . '/' . $carbon->format('Y-m') . "_$account.pdf";
        file_put_contents($fileName, $result->return);

        return $fileName;
    }

    /**
     * Преобразует данные JSON полученные от 1С в удобочитаемый массив
     * 
     * @param string $json
     * @return array Данные в читаемом виде
     */
    protected function parseJson($json)
    {
        $array = json_decode($json, true, 10, JSON_THROW_ON_ERROR);

        // Прочитать названия столбцов
        $columns = [];
        foreach ($array['#value']['column'] as $key => $value) {
            $columns[$key] = $value['Name'];
        }

        // Прочитать строки
        $rows = [];
        foreach ($array['#value']['row'] as $rowValue) {
            $row = [];
            foreach ($rowValue as $key => $value) {
                if (isset($value['#value'])) {
                    $row[$columns[$key]] = $value['#value'];
                } else {
                    $row[$columns[$key]] = '';
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
