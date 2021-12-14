<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Accrual;

class GetPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments {from=yesterday} {to=now}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get payments';

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
        $from = new Carbon($this->argument('from'));
        $to = new Carbon($this->argument('to'));

        /**
         * Получить данные
         */
        $data = [
            'status' => 200,
            'title' => 'OK',
            'data' => [
                'from' => $from->format('c'),
                'to' =>  $to->format('c'),
                'payments' => []
            ]
        ];

        $accruals = Accrual::where('paid_at', '>=', $from)
            ->where('paid_at', '<', $to)
            ->get();

        foreach ($accruals as $accrual) {
            echo 'date;uuid;account;sum' . PHP_EOL;
            echo (new Carbon($accrual->paid_at))->format('c') . ';';
            echo $accrual->uuid . ';';
            echo $accrual->account . ';';
            echo $accrual->sum * 100 . PHP_EOL;
        }
        return Command::SUCCESS;
    }
}
