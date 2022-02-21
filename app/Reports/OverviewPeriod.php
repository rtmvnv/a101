<?php

namespace App\Reports;

use Carbon\Carbon;
use App\Models\Accrual;

class OverviewPeriod
{
    public function __invoke($period)
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
