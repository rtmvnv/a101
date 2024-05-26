<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\OrangeData;

class HealthCheck
{
    public function __invoke()
    {
        $orangedata = new OrangeData();
        $checks = [
            'database' => $this->checkDatabase(),
            'orangedata_inn' => $orangedata->checkInn(),
            'orangedata_validity' => $orangedata->checkValidityDate(),
            'orangedata_api' => $orangedata->checkApi(),

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
}
