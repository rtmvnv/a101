<?php

namespace App\Console\Commands\Monit;

use Illuminate\Console\Command;
use App\MoneyMailRu\MoneyMailRu as MoneyMailRuClass;

class MoneyMailRu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monit:moneymailru';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks that can connect to MoneyMailRu';

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
        $module = app(MoneyMailRuClass::class);
        $response = $module->request('merchant/info');

        if ($response['result_code'] !== 0) {
            echo "status:error; result_code:{$response['result_code']}; result_message:{$response['result_message']}";
            return 1;
        }

        if ($response['header']['status'] !== 'OK') {
            echo "status:{$response['header']['status']}; "
                . 'code:' . (isset($response['header']['error']['code']) ? $response['header']['error']['code'] : 'unknown') . '; '
                . 'message:' . (isset($response['header']['error']['message']) ? $response['header']['error']['message'] : 'unknown') . '; '
                . 'error_id:' . (isset($response['header']['error']['error_id']) ? $response['header']['error']['error_id'] : 'unknown');
            return 1;
        }

        echo "status:{$response['header']['status']}";
        return 0;
    }
}
