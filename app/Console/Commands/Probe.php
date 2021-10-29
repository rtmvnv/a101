<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
        $message = new UniOneMessage();
        $message->to('null@vic-insurance.ru', 'No Name');
        $message->subject('subject');
        $message->plain('body');

        $result = $message->send();

        print_r($result);
    }
}
