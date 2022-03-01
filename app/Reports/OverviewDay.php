<?php

namespace App\Reports;

use Carbon\Carbon;
use App\Models\Accrual;

class OverviewDay
{
    public function __invoke($day)
    {
        $result = [];
        $start = (new Carbon($day))->startOfDay();
        $finish = (new Carbon($day))->startOfDay()->addHours(24);

        /**
         * Детализация числа недоставленных
         */
        $accounts = Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->distinct()
            ->get();

        $statistics = [];
        foreach ($accounts as $account) {
            $successfulCount = Accrual::where('account', $account['account'])
                ->where('period', $account['period'])
                ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
                ->count();

            if ($successfulCount > 0) {
                continue;
            }

            $notDeliveredAccount = Accrual::where('account', $account['account'])
                ->where('period', $account['period'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (empty($notDeliveredAccount['unione_status'])) {
                $unioneStatus = 'not_sent';
            } else {
                $unioneStatus = $notDeliveredAccount['unione_status'];
            }

            (isset($statistics[$unioneStatus])) ? $statistics[$unioneStatus] += 1 : $statistics[$unioneStatus] = 1;
        }

        // Детализация числа недоставленных
        $result['statistics'] = '';
        foreach ($statistics as $key => $value) {
            $result['statistics'] .= $key . ': ' . $value . PHP_EOL;
        }
        trim($result['statistics']);

        $result['title'] = $start->translatedFormat('d.m.Y');
        $result['total'] = count(Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->distinct()
            ->get());
        $result['delivered'] = Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
            ->distinct()
            ->count();
        $result['paid'] = count(Accrual::select('account', 'period')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $finish)
            ->where('paid_at', '<>', null)
            ->distinct()
            ->get());
        $result['not_delivered'] = $result['total'] - $result['delivered'];

        $result['not_delivered_link'] = route(
            'delivery',
            ['start' => $start->format('Y-m-d'), 'interval' => 'day']
        );

        return $result;
    }
}
