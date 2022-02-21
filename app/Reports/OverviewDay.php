<?php

namespace App\Reports;

use Carbon\Carbon;
use App\Models\Accrual;

class OverviewDay
{
    public function __invoke($day)
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
        $result['title'] = $start->translatedFormat('D d.m.Y');
        return $result;
    }
}
