<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Accrual;
use App\UniOne\Message;
use App\MoneyMailRu\MoneyMailRu;
use App\A101;
use NumberFormatter;

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
        // echo url('/');
        // exit;

        $a101 = new A101();
        echo $a101->postApiAccrualsSignature([
            'sum' => -23,
            'period' => '202111',
            'email' => 'test@example.com',
            'account' => 'ИК123456',
            'name' => 'Имя User-Name',
        ]);
        exit;

        $accrual = Accrual::where('uuid', '9d872825-3431-41ff-bfcf-97eb9b3a487f')->first();
        print_r($accrual->toArray());
    }
}
