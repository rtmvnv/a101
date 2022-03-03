<?php

namespace App\Reports;

use Carbon\Carbon;
use App\UniOne\UniOne;

/**
 * Список последних вебхуков, полученных от UniSender
 */
class EmailEvents
{
    public function __invoke($email)
    {
        $events = app('mongo_events');

        $query = [
            // 'datetime' => array('$gte' => new UTCDateTime(1000 * strtotime('-1 month'))),
            'message' => 'incoming-api-unione',
            'context.request.event_name' => 'transactional_email_status',
            'context.request.email' => $email,
            // 'context.request.status' => ['$in' => ['hard_bounced', 'spam', 'unsubscribed']],
        ];
        $records = $events->find($query, ['limit' => 50, 'sort' => ['_id' => -1]]);

        $result = [];
        foreach ($records as $record) {
            // print_r($record['context']['request']);
            if (
                isset($record->context->request->delivery_info->delivery_status)
                and isset($record->context->request->delivery_info->destination_response)
            ) {
                $explanation = UniOne::explainError(
                    $record->context->request->status,
                    $record->context->request->delivery_info->delivery_status,
                    $record->context->request->delivery_info->destination_response
                );
                $deliveryStatus = $record->context->request->delivery_info->delivery_status;
                $deliveryResponse = $record->context->request->delivery_info->destination_response;
        } else {
                $explanation = '';
                $deliveryStatus = '';
                $deliveryResponse = '';
            }
            $result[] = [
                'datetime' => new Carbon($record->context->request->event_time, 'Europe/Moscow'),
                'id' => $record->_id,
                'status' => $record->context->request->status,
                'delivery_status' => $deliveryStatus,
                'destination_response' => $deliveryResponse,
                'explanation' => $explanation,
            ];
        }

        return $result;
    }
}
