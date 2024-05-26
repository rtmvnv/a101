<?php

namespace App\Http\Controllers;

use App\HealthCheck;


class HealthCheckController extends Controller
{
    public function check()
    {
        $checks = (new HealthCheck())();
        $status = 'OK';
        foreach ($checks as $check) {
            if ($check !== True) {
                $status = 'FAIL';
            }
        }
        return response()->json(['status' => $status, 'checks' => $checks]);
    }
}
