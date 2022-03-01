<?php

namespace App\Reports;

use Carbon\Carbon;
use App\Models\Accrual;

class OverviewPeriod
{
    public function __invoke($period)
    {
        $start = new Carbon($period);
        $start->startOfMonth();
        $periodString = $start->format('Ym');

        $result = [
            'title' => (new Carbon($period))->translatedFormat('F Y'),
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
        $result['not_delivered_link'] = route(
            'delivery',
            ['start' => $start->format('Y-m-d'), 'interval' => 'month']
        );
        // ddd(Accrual::select('account', 'period')->where('period', $periodString)->distinct()->get()->toArray());
        return $result;
    }
}
