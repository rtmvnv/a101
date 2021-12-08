<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Accrual;
use App\A101;
use App\UniOne;
use App\UniOneMessage;
use App\UniOneException;

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
        $a101 = new A101();
        echo $a101->apiAccrualsPostSignature([
            'sum' => 0,
            'period' => '202111',
            'email' => 'test@example.com',
            'account' => 'ИК123456',
            'name' => 'Имя User-Name',
        ]);

        // $accrual = Accrual::where('uuid', '320ade49-5bd8-4e58-b748-7c8ddfecf3eb')->first();
        // print_r($accrual->status);
    }
}
