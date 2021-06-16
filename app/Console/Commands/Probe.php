<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MoneyMailRu;
use App\Models\Accrual;
use Illuminate\Support\Str;


class Probe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'probe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Try some code during development';

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
        $a = Accrual::first();
        echo $a->valid_till;

        $module = new MoneyMailRu();
            // print_r($module->transactionStart(user_id: '123', amount: 10, description: 'Тестовый платеж'));

                return 0;
    }
}
