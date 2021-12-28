<?php

namespace App\Console\Commands\Monit;

use Illuminate\Console\Command;
use App\UniOne\UniOne as UniOneClass;

class UniOne extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monit:unione';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks that can connect to UniOne';

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
        $unione = new UniOneClass(config('services.unione.api_key'));
        $result = $unione->systemInfo();
        if ($result['status'] !== 'success') {
            echo "status:{$result['status']}; code:{$result['code']}; message:{$result['message']}";
            return 1;
        }
        echo "status:{$result['status']}";
        return 0;
    }
}
