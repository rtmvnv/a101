<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Reports\NotDeliveredInterval;
use App\Reports\NotDeliveredPeriod;

class DeliveryController extends Controller
{
    public function __invoke(Request $request)
    {
        $start = $request->input('start', 'yesterday');
        $startDate = (new Carbon($start, 'Europe/Moscow'))->setTime(0, 0, 0, 0);
        $interval = $request->input('interval', 'day');

        switch ($interval) {
            case 'week':
                $interval = 'week';
                $accounts = (new NotDeliveredInterval())($start, $interval);
                break;

            case 'month':
                $interval = 'month';
                $accounts = (new NotDeliveredPeriod())($start);
                break;

            default:
                $interval = 'day';
                $accounts = (new NotDeliveredInterval())($start, $interval);
                break;
        }

        /**
         * Дописать в результаты ссылки на страницы интерфейса
         */
        foreach ($accounts as $account) {
            $account->account_link = route('account', ['account' => $account->account]);
            $emails = preg_split('/( |,|;)/', mb_strtolower($account->email), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($emails as $email) {
                $account->emails[] = (object)[
                    'address' => $email,
                    'link' => route('emails', ['email' => $email]),
                ];
            }
        }

        return view('internal/delivery', [
            'start' => $startDate->format('Y-m-d'),
            'interval' => $interval,
            'accounts' => $accounts,
        ]);
    }
}
