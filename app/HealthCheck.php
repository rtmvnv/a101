<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\OrangeData;
use App\UniOne\Health as UniOneHealth;
use App\MoneyMailRu\MoneyMailRu;
use MongoDB\BSON\UTCDateTime;


class HealthCheck
{
    public function __invoke()
    {
        $orangedata = new OrangeData();
        $unione = new UniOneHealth();
        $mailru = new MoneyMailRu;
        $checks = [
            'exceptions' => $this->checkExceptions(),
            'database' => $this->checkDatabase(),
            'orangedata_inn' => $orangedata->checkInn(),
            'orangedata_validity' => $orangedata->checkValidityDate(),
            'orangedata_api' => $orangedata->checkApi(),
            'unione_api' => $unione->checkApi(),
            'unione_webhook' => $unione->checkWebhook(),
            'mailru' => $mailru->health(),

            // 'externalService' => $this->checkExternalService(),
        ];

        return $checks;
    }

    protected function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    protected function checkExternalService()
    {
        try {
            $response = Http::get('https://api.example.com/health');
            return $response->successful() ? ['status' => 'OK'] : ['status' => 'FAIL'];
        } catch (\Exception $e) {
            return ['status' => 'FAIL', 'message' => $e->getMessage()];
        }
    }

    protected function checkExceptions() {
        try {
            $events = app('mongo_events');
            $query = [
                'context.response.exception.message' => [ '$exists' => true ],
                'datetime' => array('$gte' => new UTCDateTime(1000 * strtotime('-1 minutes'))),
            ];
            $count = $events->count($query);
            if ($count > 0) {
                return ($events->findOne($query))['context']['response']['exception']['message'];
            }
            return true;
        } catch (\Throwable $th) {
            return $th->getMessage() . ' [' . $th->getFile() . ':' . $th->getLine() . ']';
        }
    }
}
