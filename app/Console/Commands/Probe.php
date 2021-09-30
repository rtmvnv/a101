<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\MoneyMailRu;
use App\Models\Accrual;
use SoapClient;

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
        ;
        $client = new SoapClient(
            // storage_path('app/MailExchange.1cws.xml'),
            'http://10.75.0.120/test_gkh5/ws/MailExchange.1cws',
            [
                'login' => "victoriya", //логин пользователя к базе 1С
                'password' => "Ci8DA4ba", //пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true
            ]
        );
        return 0;
    }
}
