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
use App\Reports\FailedEmails;

class ReportFailedEmails extends Command
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
        $records = (new FailedEmails())();

        $plain = '';
        foreach ($records as $record) {
            $plain .= $record['email'] . ' ' . $record['account']
                . PHP_EOL . $record['explanation']
                . PHP_EOL . $record['status']
                . ' ' . $record['delivery_status']
                . ' ' . $record['destination_response']
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
