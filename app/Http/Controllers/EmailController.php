<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reports\EmailEvents;
use Illuminate\Support\Facades\DB;
use App\UniOne\UniOne;

class EmailController extends Controller
{
    public function show(Request $request)
    {
        $data = [
            'email' => $request->get('email', null),
            'suppression' => ['message' => '', 'show_button' => false],
            'alert' => ['message' => '', 'type' => 'info'], // info|success|warning|danger
            'accounts' => [],
            'events' => [],
        ];

        if (empty($data['email'])) {
            return view('internal/email', $data);
        }

        /**
         * Список лицевых счетов
         */
        $accountsList = DB::table('accruals')
            ->select('account')
            ->where('email', 'like', '%' . $data['email'] . '%')
            ->distinct()
            ->get();
        foreach ($accountsList as $item) {
            $data['accounts'][] = [
                'value' => $item->account,
                'link' => route('account', ['account' => $item->account], false),
            ];
        }

        $unione = app(UniOne::class);

        /**
         * Снятие блокировки
         */
        if ($request->get('unblock', false)) {
            $requestBody = ['email' => $data['email']];
            $response = $unione->request('suppression/delete.json', $requestBody);
            switch ($response['status']) {
                case 'success':
                    $data['alert']['message'] = 'Блокировка снята';
                    break;

                case 'error':
                    $data['alert'] = [
                        'message' => 'Блокировка не снята. Код: ' . $response['code']
                            . '. Сообщение: ' . $response['message'],
                        'type' => 'warning',
                    ];
                    break;

                default:
                    $data['alert'] = [
                        'message' => 'Ошибка снятия блокировки',
                        'type' => 'warning',
                    ];
                    break;
            }
        }

        /**
         * Статус блокировки
         * {
         *   "status": "success",
         *   "email": "null@vic-insurance.ru",
         *   "suppressions": []
         * }
         *
         * {
         *   "status": "error",
         *   "code": 2902,
         *   "message": "Error ID:94DAA138-972C-11EC-94EC-B6313E7DDF87. Error in 'email' field. This value 'test1' is not a valid email address."
         * }
         *
         * {
         *   "status": "success",
         *   "email": "alextikroti@yandex.ru",
         *   "suppressions": [{
         *       "cause": "permanent_unavailable",
         *       "is_deletable": true,
         *       "created": "2021-12-30 15:15:33"
         *     }]
         * }
         */
        $requestBody = [
            'email' => $data['email'],
            'all_projects' => true,
        ];
        $response = $unione->request('suppression/get.json', $requestBody);
        switch ($response['status']) {
            case 'success':
                if (empty($response['suppressions'])) {
                    $data['suppression'] = [
                        'message' => '',
                        'show_button' => false,
                    ];
                } else {
                    $data['suppression'] = [
                        'message' => 'Адрес в списке блокировки. '
                            . 'Причина: '
                            . $response['suppressions'][0]['cause']
                            . '. Дата: ' . $response['suppressions'][0]['created'],
                        'show_button' => false,
                    ];
                    if ($response['suppressions'][0]['is_deletable']) {
                        $data['suppression']['show_button'] = true;
                    }
                }
                break;

            case 'error':
                $data['suppression'] = [
                    'message' => 'Статус блокировки неизвестен. Код: '
                        . $response['code']
                        . '. Сообщение: ' . $response['message'],
                    'show_button' => false,
                ];
                break;

            default:
                $data['suppression'] = [
                    'message' => 'Статус блокировки неизвестен',
                    'show_button' => false,
                ];
                break;
        }


        /**
         * Список ошибок отправки
         */
        $data['events'] = (new EmailEvents())($data['email']);

        /**
         * Валидация
         */
        $response = $unione->validateEmail($data['email']);
        $data['validation1'] = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $data['validation'] = $response;

        return view('internal/email', $data);
    }
}
