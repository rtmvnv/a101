<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\A101;
use Carbon\Carbon;

class Reconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a101:reconciliation {date=yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform reconciliation from an e-mail';

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
        // $count = $a101->receiveAccruals();
        $date = new Carbon($this->argument('date'));
        $this->info("Reconciliation requested for " . $date->format('Y-m-d'));
        return 0;
    }
}
