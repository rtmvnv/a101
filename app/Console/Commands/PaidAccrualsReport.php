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

class PaidAccrualsReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a101:report {date=yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the paid accruals report to A101';

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
        $dateString = (new Carbon($this->argument('date')))->translatedFormat('d F Y');

        $from = (new Carbon($this->argument('date')))->startOfDay();
        $to = (new Carbon($this->argument('date')))->endOfDay();

        $total = Accrual::where('paid_at', '>=', $from)
            ->where('paid_at', '<=', $to)
            ->sum('sum');

        $count = Accrual::where('paid_at', '>=', $from)
            ->where('paid_at', '<=', $to)
            ->count();

        $plain = view(
            'paid_accruals_report',
            [
                'date_string' => $dateString,
                'count' => $count,
                'total' => $total,
            ],
        )->render();

        $message = new Message();
        $message->to(env('A101_EMAIL'))
            ->subject("Реестр платежей А101 за {$dateString}")
            ->plain($plain)
            ->addAttachment(
                'application/xlsx',
                "Реестр платежей А101 за {$dateString}.xlsx",
                base64_encode(Excel::raw(new PaidAccrualsExport($this->argument('date')), \Maatwebsite\Excel\Excel::XLSX))
            );

        $unione = new UniOne();
        $result = $unione->emailSend($message);
        if ($result['status'] !== 'success') {
            Log::error('Failed sending paid accruals report' . (isset($result['message']) ? '. ' . $result['message'] : ''));
            print_r($result);
            return 1;
        }

        return 0;
    }
}
