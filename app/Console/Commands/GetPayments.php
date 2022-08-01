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
    protected $description = 'Get payments.' . PHP_EOL . 'example: artisan payments -- "-1 week"';

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

        $result = [];
        foreach ($accruals as $accrual) {
            $result[] = [
                (new Carbon($accrual->paid_at))->format('c'),
                $accrual->uuid,
                $accrual->account,
                $accrual->sum * 100,
            ];
        }

        $this->table(
            ['date', 'uuid', 'account', 'sum'],
            $result
        );

        return Command::SUCCESS;
    }
}
