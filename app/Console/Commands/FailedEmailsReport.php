<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Excel;
use App\Exports\PaidAccrualsExport;
use App\UniOne\UniOne;
use App\UniOne\Message;
use App\Models\Accrual;

class FailedEmailsReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:failed_emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the failed emails report';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $events = (new \MongoDB\Client(
            'mongodb://'
                . env('MONGODB_USERNAME')
                . ':'
                . env('MONGODB_PASSWORD')
                . '@'
                . env('MONGODB_SERVER')
                . ':'
                . env('MONGODB_PORT')
                . '/?authSource='
                . env('MONGODB_AUTH_SOURCE'),
        ))->a101->events;

        $pipeline = [
            [
                '$match' =>
                [
                    'datetime' => array('$gte' => new \MongoDB\BSON\UTCDateTime(1000 * strtotime('-1 week'))),
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
        $result = $events->aggregate($pipeline);

        /*
         * Найти квитанции по email
         */
        $plain = '';
        foreach ($result as $value) {
            $accrual = Accrual::where('email', 'like', '%' . $value['_id'] . '%')
                ->orderBy('id', 'desc')
                ->first();

            if (empty($accrual)) {
                continue;
            }

            $plain .= $value->_id . ' ' . $accrual->account
                . PHP_EOL . UniOne::explainError($value->status, $value->delivery_status, $value->destination_response)
                . PHP_EOL . $value->status
                . ' ' . $value->delivery_status
                . ' ' . $value->destination_response
                . PHP_EOL . PHP_EOL;
        }

        $message = new Message();
        $message->to(env('REPORTS_FAILED_EMAILS'))
            ->subject('Отчет об ошибках доставки писем за неделю')
            ->plain($plain);

        $unione = app(UniOne::class);
        $result = $unione->emailSend($message);
        if ($result['status'] !== 'success') {
            Log::error('Failed sending failed emails report' . (isset($result['message']) ? '. ' . $result['message'] : ''));
            print_r($result);
            return 1;
        }

        return 0;
    }
}
