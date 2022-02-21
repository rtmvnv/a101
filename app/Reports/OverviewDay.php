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

        $result = [];
        $result['title'] = $start->translatedFormat('D d.m.Y');

        /**
         * Статистика по статусам недоставленных писем
         */
        $totalList = Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->distinct()
            ->get();

        $statistics = [];
        foreach ($totalList as $account) {
            $accrual = Accrual::where('account', $account['account'])
                ->where('period', $account['period'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (
                $accrual['unione_status'] == 'delivered'
                or  $accrual['unione_status'] == 'opened'
                or  $accrual['unione_status'] == 'clicked'
            ) {
                continue;
            }

            if (empty($accrual['unione_status'])) {
                $unioneStatus = 'not_sent';
            } else {
                $unioneStatus = $accrual['unione_status'];
            }

            if (!isset($statistics[$unioneStatus])) {
                $statistics[$unioneStatus] = 1;
            } else {
                $statistics[$unioneStatus] += 1;
            }
        }
        $result['statistics'] = '';
        foreach ($statistics as $key => $value) {
            $result['statistics'] .= $key . ': ' . $value . PHP_EOL;
        }
        trim($result['statistics']);

        $result['total'] = count($totalList);
        $result['delivered'] = count(Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
            ->distinct()
            ->get());
        $result['paid'] = count(Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->where('paid_at', '<>', null)
            ->distinct()
            ->get());
        $result['not_delivered'] = $result['total'] - $result['delivered'];
        $result['title'] = $start->translatedFormat('D d.m.Y');
        return $result;
    }
}
