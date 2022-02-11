<?php

namespace App\Reports;

use App\UniOne\UniOne;
use App\Models\Accrual;
use MongoDB\Client;
use MongoDB\BSON\UTCDateTime;

/**
 * Список ошибок отправки писем за последнее время
 */
class FailedEmails
{
    protected $since;

    public function __construct($since = '-1 week')
    {
        $this->since = $since;
    }

    public function __invoke()
    {
        $events = (new Client(
            'mongodb://' . env('MONGODB_USERNAME') . ':' . env('MONGODB_PASSWORD')
                . '@' . env('MONGODB_SERVER') . ':' . env('MONGODB_PORT')
                . '/?authSource=' . env('MONGODB_AUTH_SOURCE'),
        ))->a101->events;

        $pipeline = [
            [
                '$match' =>
                [
                    'datetime' => array('$gte' => new UTCDateTime(1000 * strtotime($this->since))),
                    'message' => 'incoming-api-unione',
                    'context.request.event_name' => 'transactional_email_status',
                    'context.request.status' => ['$in' => ['hard_bounced', 'soft_bounced', 'spam', 'unsubscribed']],
                ]
            ], [
                '$group' =>
                [
                    '_id' => '$context.request.email',
                    'status' => ['$last' => '$context.request.status'],
                    'delivery_status' => ['$last' => '$context.request.delivery_info.delivery_status'],
                    'destination_response' => ['$last' => '$context.request.delivery_info.destination_response'],
                ],
            ],
        ];
        $records = $events->aggregate($pipeline);

        /*
         * Найти квитанции по email
         */
        $result = [];
        foreach ($records as $record) {
            $accrual = Accrual::where('email', 'like', '%' . $record['_id'] . '%')
                ->orderBy('id', 'desc')
                ->first();

            if (empty($accrual)) {
                continue;
            }

            $result[] = [
                'email' => $record->_id,
                'account' => $accrual->account,
                'explanation' => UniOne::explainError($record->status, $record->delivery_status, $record->destination_response),
                'status' => $record->status,
                'delivery_status' => $record->delivery_status,
                'destination_response' => $record->destination_response,
            ];
        }

        return $result;
    }
}
