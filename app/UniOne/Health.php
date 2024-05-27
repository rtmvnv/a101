<?php

namespace App\UniOne;

use App\UniOne\UniOne;

class Health
{
    /**
     * Checks that can connect to UniOne
     */
    public function checkApi() {
        $module = new UniOne;
        $result = $module->systemInfo();
        if ($result['status'] !== 'success') {
            return "status:{$result['status']}; code:{$result['code']}; message:{$result['message']}";
        }
        return true;
    }

    /**
     * Checks that webhook is set
     */
    public function checkWebhook() {
        $module = new UniOne;
        $url = route('unione');
        $requestBody = ['url' => $url];
        $result = $module->request('webhook/get.json', $requestBody);
        if ($result['status'] !== 'success') {
            return "status:{$result['status']}; code:{$result['code']}; message:{$result['message']}";
        }
        return true;
    }
}