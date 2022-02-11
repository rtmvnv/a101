<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reports\AccrualsOverviewDay;
use App\Reports\AccrualsOverviewPeriod;

class Dashboard extends Controller
{

    public function show(Request $request)
    {
        $accrualsOverviewDay = new AccrualsOverviewDay();
        $data['day0'] = $accrualsOverviewDay('today');
        $data['day1'] = $accrualsOverviewDay('yesterday');
        $data['day2'] = $accrualsOverviewDay('-2 days');
        $data['day3'] = $accrualsOverviewDay('-3 days');
        $data['day4'] = $accrualsOverviewDay('-4 days');
        $data['day5'] = $accrualsOverviewDay('-5 days');
        $data['day6'] = $accrualsOverviewDay('-6 days');

        $accrualsOverviewPeriod = new AccrualsOverviewPeriod();
        $data['current_month'] = $accrualsOverviewPeriod('this month');
        $data['previous_month'] = $accrualsOverviewPeriod('previous month');
        $data['preprevious_month'] = $accrualsOverviewPeriod('-2 months');

        $data['emails'] = [
            ['id' => 111, 'status' => 'failed', 'account' => 'АБ1234']

        ];
        return view('internal/dashboard', $data);
    }
}
