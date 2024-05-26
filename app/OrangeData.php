<?php

namespace App;

use orangedata\orangedata_client;

class OrangeData
{
    /**
     * Проверка ИНН, указанного в сертификате
     */
    public function checkInn()
    {
        $client_crt = file_get_contents(storage_path('app/orangedata/client.crt'));
        $ssl = openssl_x509_parse($client_crt);
        if ($ssl['subject']['ST'] !== config('services.orangedata.inn')) {
            return 'Сертификат client.crt не соответствует ИНН';
        }
        return true;
    }

    /**
     * Проверка срок действия сертификата
     */
    public function checkValidityDate()
    {
        $client_crt = file_get_contents(storage_path('app/orangedata/client.crt'));
        $ssl = openssl_x509_parse($client_crt);
        $valid_to = (new \DateTime())->setTimestamp((int)$ssl['validTo_time_t']);
        if ($valid_to < (new \DateTime())->add(new \DateInterval('P30D'))) {
            return 'Срок действия сертификата client.crt истекает ' . $valid_to->format('d.m.Y');
        }
        return true;
    }

    /**
     * Проверка запросом к API соответствия сертификатов на стороне OrangeData
     */
    public function checkApi()
    {
        $orangeData = app(orangedata_client::class);
        try {
            return $orangeData->check('Main', config('services.orangedata.inn'));
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
