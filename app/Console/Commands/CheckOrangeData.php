<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use orangedata\orangedata_client;


class CheckOrangeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orangedata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверяет сертификат Orange Data';

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
        $result = [
            'inn' => false,
            'expiration' => false,
            'api' => false,
        ];

        // Проверка сертификата
        $client_crt = file_get_contents(storage_path('app/orangedata/client.crt'));
        $ssl = openssl_x509_parse($client_crt);
        if ($ssl['subject']['ST'] === config('services.orangedata.inn')) {
            $result['inn'] = true;
        } else {
            echo 'Неверный ИНН' . PHP_EOL;
        }

        // Проверка срока действия
        $valid_to = (new \DateTime())->setTimestamp((int)$ssl['validTo_time_t']);
        if ($valid_to > (new \DateTime())->add(new \DateInterval('P30D'))) {
            $result['expiration'] = true;
        } else {
            echo 'Срок действия сертификата истекает ' . $valid_to->format('d.m.Y') . PHP_EOL;
        }

        // Проверка запросом к API
        $orangeData = app(orangedata_client::class);
        $orangeData->is_debug();
        try {
            $response = $orangeData->check('Main', config('services.orangedata.inn'));
            if ($response !== true) {
                echo $response . PHP_EOL;
            } else {
                $result['api'] = true;
            }
        } catch (\Throwable $th) {
            echo $th->getMessage();
            return Command::FAILURE;
        }

        if (in_array(false, $result, true)) {
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            return Command::FAILURE;
        }

        echo 'ok';
        return Command::SUCCESS;
    }
}
