<?php

namespace App\Console\Commands;

use App\XlsxToPdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Carbon\CarbonImmutable;
use orangedata\orangedata_client;

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
        $accrual = [
            'email' => 'aivanov@vic-insurance.ru',
            'sum' => 100,
            'account' => 'ИК123456',
            'period_text' => 'Январь 2023'
        ];

        $record = [];
        try {
            $requestTime = CarbonImmutable::now();
            $record['request_time'] = $requestTime->format('c');

            $orangeData = new orangedata_client(
                [
                    'inn' => env('ORANGEDATA_INN'),
                    'api_url' => env('ORANGEDATA_URL'),
                    'sign_pkey' => storage_path('app/orangedata/private_key.pem'),
                    'ssl_client_key' => storage_path('app/orangedata/client.key'),
                    'ssl_client_crt' => storage_path('app/orangedata/client.crt'),
                    'ssl_ca_cert' => storage_path('app/orangedata/cacert.pem'),
                    'ssl_client_crt_pass' => env('ORANGEDATA_PASS'),
                ]
            );

            if (App::environment('local')) {
                $orangeData->is_debug();
            }

            $orangeData->create_order([
                'id' => random_int(1, PHP_INT_MAX),
                'type' => 1,
                'customerContact' => $accrual['email'],
                'taxationSystem' => 0,
                'key' => env('ORANGEDATA_INN'),
                'callbackUrl' => route('orangedata'),
            ])
                ->add_position_to_order([
                    'quantity' => '1',
                    'price' => $accrual['sum'],
                    'tax' => 1,
                    'text' => "Квитанция по лицевому счету {$accrual['account']} за {$accrual['period_text']}",
                    'paymentMethodType' => 4,
                    'paymentSubjectType' => 13,
                    'supplierInfo' => [
                        'phoneNumbers' => ['+74956486777'],
                        'name' => 'А101-Комфорт',
                    ],
                    'supplierINN' => env('ORANGEDATA_INN'),
                ])
                ->add_payment_to_order([
                    'type' => 16,
                    'amount' => $accrual['sum'],
                ]);

            $record['request'] = $orangeData->get_order();
            $result = $orangeData->send_order();
            $responseTime = CarbonImmutable::now();

            echo 'SEND ORDER' . PHP_EOL;
            var_dump($result);
        } catch (\Throwable $th) {
            $response['result_code'] = 23625490;
            $response['result_message'] = $th->getMessage();
            $response['exception'] = [
                'message' => $th->getMessage(),
                'code' => $th->getCode(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ];
        } finally {
            $record['response'] = $response;
            $record['response_time'] = $responseTime->format('c');
            $record['elapsed'] = $responseTime->floatDiffInSeconds($requestTime);
            Log::info('outgoing-orangedata', $record);
        }
    }
}
