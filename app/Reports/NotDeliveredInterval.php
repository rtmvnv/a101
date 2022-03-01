<?php

namespace App\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Список лицевых счетов, на которые не удалось отправить квитанцию за заданный промежуток времени
 */
class NotDeliveredInterval
{
    public function __invoke($start, $interval)
    {
        $startDate = (new Carbon($start, 'Europe/Moscow'))->setTime(0, 0, 0, 0);

        switch ($interval) {
            case 'day':
                $finishDate = (clone $startDate)->addDay();
                break;

            case 'week':
                $finishDate = (clone $startDate)->addWeek();
                break;

            default:
                throw new Exception('Unknown interval: ' . $interval, 29794521);
                break;
        }

        $allAccounts = DB::table('accruals')
            ->select('account', 'period')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $finishDate)
            ->distinct()
            ->get();

        $accounts = [];
        foreach ($allAccounts as $account) {
            // Check if there is a successful accrual
            $successfulAccruals = DB::table('accruals')
                ->select('account', 'period')
                ->where('account', $account->account)
                ->where('period', $account->period)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<', $finishDate)
                ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
                ->count();
            if ($successfulAccruals > 0) {
                continue;
            }

            $accounts[] = DB::table('accruals')
                ->where('account', $account->account)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<', $finishDate)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $accounts;
    }
}
