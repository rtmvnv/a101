<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Soap1C;

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
        $soap1C = new Soap1C();

        // $rows = $soap1C->getAllAccrualsFromPeriod('august 2021');
        // print_r($rows);

        $result = $soap1C->GetAccrualFromPeriodPDF(
            'august 2021',
            '28cd2c3c-64eb-11e9-8114-0025902b4045',
            '28cd2c3d-64eb-11e9-8114-0025902b4045',
        );
        print_r($result);
    }
}
