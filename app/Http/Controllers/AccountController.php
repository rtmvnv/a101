<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function get(Request $request)
    {
        return redirect()->route('account', $request->get('account', ''));
    }

    public function show(Request $request, $account = '')
    {
        if (empty($account)) {
            return view('internal/account', ['account' => '', 'accruals' => []]);
        }

        // $account = $request->get('account');
        $accrualsByPeriod = DB::table('accruals')
            ->select('account', 'period', 'sum', 'email', 'name', 'payee')
            ->where('account', $account)
            ->groupBy('account', 'period', 'sum', 'email', 'name', 'payee')
            ->get();

        $accruals = [];
        foreach ($accrualsByPeriod as $accrual) {
            $item = DB::table('accruals')
                ->where('account', $account)
                ->where('period', $accrual->period)
                ->where('sum', $accrual->sum)
                ->where('email', $accrual->email)
                ->where('name', $accrual->name)
                ->where('payee', $accrual->payee)
                ->orderBy('created_at', 'desc')
                ->first();

            $item->attempts = DB::table('accruals')
                ->where('account', $account)
                ->where('period', $accrual->period)
                ->where('sum', $accrual->sum)
                ->where('email', $accrual->email)
                ->where('name', $accrual->name)
                ->where('payee', $accrual->payee)
                ->orderBy('created_at', 'desc')
                ->count();

            $emails = preg_split('/( |,|;)/', mb_strtolower($accrual->email), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($emails as $email) {
                $item->emails[] = (object)[
                    'address' => $email,
                    'link' => route('emails', ['email' => $email]),
                ];
            }
            $accruals[] = $item;
        }

        return view('internal/account', [
            'account' => $account,
            'accruals' => $accruals,
        ]);
    }
}
