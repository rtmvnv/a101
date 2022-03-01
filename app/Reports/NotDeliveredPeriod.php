<?php

namespace App\Reports;

use Illuminate\Support\Facades\DB;

/**
 * Список лицевых счетов, на которые не удалось отправить квитанцию за период
 */
class NotDeliveredPeriod
{
    public function __invoke($period)
    {
        $allAccounts = DB::table('accruals')
            ->select('account')
            ->where('period', $period)
            ->distinct()
            ->get();

        $accounts = [];
        foreach ($allAccounts as $account) {
            // Check if there is a successful accrual
            $successfulAccruals = DB::table('accruals')
                ->select('account', 'period')
                ->where('account', $account->account)
                ->where('period', $period)
                ->whereIn('unione_status', ['delivered', 'opened', 'clicked'])
                ->count();
            if ($successfulAccruals > 0) {
                continue;
            }

            $accounts[] = DB::table('accruals')
                ->where('account', $account->account)
                ->where('period', $period)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        return $accounts;
    }
}
