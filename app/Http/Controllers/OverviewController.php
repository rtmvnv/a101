<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reports\OverviewDay;
use App\Reports\OverviewPeriod;

class OverviewController extends Controller
{

    public function show(Request $request)
    {
        $overviewDay = new OverviewDay();
        $data['day0'] = $overviewDay('today');
        $data['day1'] = $overviewDay('yesterday');
        $data['day2'] = $overviewDay('-2 days');
        $data['day3'] = $overviewDay('-3 days');
        $data['day4'] = $overviewDay('-4 days');
        $data['day5'] = $overviewDay('-5 days');
        $data['day6'] = $overviewDay('-6 days');

        $overviewPeriod = new OverviewPeriod();
        $data['current_month'] = $overviewPeriod('this month');
        $data['previous_month'] = $overviewPeriod('previous month');
        $data['preprevious_month'] = $overviewPeriod('-2 months');

        $data['emails'] = [
            ['id' => 111, 'status' => 'failed', 'account' => 'АБ1234']

        ];
        return view('internal/overview', $data);
    }
}
