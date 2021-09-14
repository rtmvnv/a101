<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\A101;

class ReceiveAccruals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a101:receive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receive the list of accruals from 1C';

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
        $count = $a101->receiveAccruals();
        echo "Received $count accruals";
        return 0;
    }
}
