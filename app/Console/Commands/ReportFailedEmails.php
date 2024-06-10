<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\UniOne\Message;
use App\Reports\FailedEmails;
use App\UniOne\UniOne;

class ReportFailedEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:failed_emails {--send}';

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
        $records = (new FailedEmails('-1 week'))();

        $plain = '';
        foreach ($records as $record) {
            $plain .= 'email: ' . $record['email']
                . PHP_EOL . 'account: ' . $record['account']
                . PHP_EOL . $record['explanation']
                . PHP_EOL . 'status: ' . $record['status']
                . PHP_EOL . 'delivery_status: ' . $record['delivery_status']
                . PHP_EOL . 'destination_response: ' . $record['destination_response']
                . PHP_EOL . PHP_EOL;
        }
        if (empty($plain)) {
            $plain = 'Нет ошибок доставки';
        }

        $message = new Message();
        $message->to(env('REPORTS_FAILED_EMAILS'))
            ->subject('Отчет об ошибках доставки писем за неделю')
            ->plain($plain);

        if ($this->option('send')) {
            $unione = app(UniOne::class);
            $result = $unione->emailSend($message);
            if ($result['status'] !== 'success') {
                Log::error('Failed sending failed emails report' . (isset($result['message']) ? '. ' . $result['message'] : ''));
                print_r($result);
                return 1;
            }
        } else {
            $this->line($plain);
        }

        return 0;
    }
}
