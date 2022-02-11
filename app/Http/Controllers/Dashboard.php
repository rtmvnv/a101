<?php

namespace App\Http\Controllers;

use App\Models\Accrual;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard extends Controller
{

    public function show(Request $request)
    {
        $data['day0'] = $this->getAccrualsOverviewDay('today');
        $data['day1'] = $this->getAccrualsOverviewDay('yesterday');
        $data['day2'] = $this->getAccrualsOverviewDay('-2 days');
        $data['day3'] = $this->getAccrualsOverviewDay('-3 days');
        $data['day4'] = $this->getAccrualsOverviewDay('-4 days');
        $data['day5'] = $this->getAccrualsOverviewDay('-5 days');
        $data['day6'] = $this->getAccrualsOverviewDay('-6 days');

        $data['current_month'] = $this->getAccrualsOverviewPeriod('this month');
        $data['previous_month'] = $this->getAccrualsOverviewPeriod('previous month');
        $data['preprevious_month'] = $this->getAccrualsOverviewPeriod('-2 months');

        $data['emails'] = [
            ['id' => 111, 'status' => 'failed', 'account' => 'АБ1234']

        ];
        return view('internal/dashboard', $data);
    }

    public function getAccrualsOverviewDay($day)
    {
        $start = (new Carbon($day))->startOfDay();
        $finish = (new Carbon($day))->startOfDay()->addHours(24);

        $result = [
            'total' => count(Accrual::select('account', 'period')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $finish)
                ->distinct()
                ->get()),
            'delivered' => count(Accrual::select('account', 'period')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $finish)
                ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
                ->distinct()
                ->get()),
            'paid' => count(Accrual::select('account', 'period')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $finish)
                ->where('paid_at', '<>', null)
                ->distinct()
                ->get()),
        ];
        $result['not_delivered'] = $result['total'] - $result['delivered'];
        $result['title'] = $start->translatedFormat('d F Y');
        return $result;
    }

    public function getAccrualsOverviewPeriod($period)
    {
        $periodString = (new Carbon($period))->format('Ym');

        $result = [
            'total' => count(Accrual::select('account', 'period')
                ->where('period', $periodString)
                ->distinct()
                ->get()),
            'delivered' => count(Accrual::select('account', 'period')
                ->where('period', $periodString)
                ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
                ->distinct()
                ->get()),
            'paid' => count(Accrual::select('account', 'period')
                ->where('period', $periodString)
                ->where('paid_at', '<>', null)
                ->distinct()
                ->get()),
        ];
        $result['not_delivered'] = $result['total'] - $result['delivered'];
        $result['title'] = (new Carbon($period))->translatedFormat('F Y');
        return $result;
    }
}
