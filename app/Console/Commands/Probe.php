<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Reports\EmailEvents;

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
        print_r((new EmailEvents())('vladimir.glavnov@iss.ru'));
        // echo route('failed-accruals', ['test1' => '1']);
    }
}
