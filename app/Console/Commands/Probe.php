<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Reports\OverviewDay;

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
        $report = new OverviewDay();
        print_r($report('19.02.2022'));

        // $accrual = Accrual::find("7cbccba5-afda-4fa4-b845-c6644a5d5a0e");
        // print_r($accrual->toArray());
    }
}
