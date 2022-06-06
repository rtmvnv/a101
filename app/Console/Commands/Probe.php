<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
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
                    // 'inn' => config('services.orangedata.inn'),
                    'inn' => '1234567890',
                    'api_url' => config('services.orangedata.url'),
                    'sign_pkey' => storage_path('app/orangedata/private_key.pem'),
                    'ssl_client_key' => storage_path('app/orangedata/client.key'),
                    'ssl_client_crt' => storage_path('app/orangedata/client.crt'),
                    'ssl_ca_cert' => storage_path('app/orangedata/cacert.pem'),
                    'ssl_client_crt_pass' => config('services.orangedata.pass'),
                ]
            );

            if (App::environment('local')) {
                $orangeData->is_debug();
            }

            $orangeData->create_order([
                // 'id' => random_int(1, PHP_INT_MAX),
                'id' => 6640319152023171367,
                'type' => 1,
                'customerContact' => $accrual['email'],
                'taxationSystem' => 0,
                'key' => config('services.orangedata.inn'),
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
                    'supplierINN' => config('services.orangedata.inn'),
                ])
                ->add_payment_to_order([
                    'type' => 16,
                    'amount' => $accrual['sum'],
                ]);

            $record['request'] = arrayCastRecursive($orangeData->get_order());
            $response = $orangeData->send_order();
            $responseTime = CarbonImmutable::now();
        } catch (\Throwable $th) {
            $response['errors'][] = "Exception. {$th->getMessage()} ({$th->getCode()} {$th->getFile()}:{$th->getLine()}";
        } finally {
            if (is_string($response)) {
                $record['response'] = json_decode($response, true, 512, JSON_OBJECT_AS_ARRAY);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $record['response'] = $response;
                }
            } else {
                $record['response'] = $response;
            }

            if (!isset($responseTime)) {
                $responseTime = CarbonImmutable::now();
            }
            $record['response_time'] = $responseTime->format('c');
            $record['elapsed'] = $responseTime->floatDiffInSeconds($requestTime);

            Log::info('outgoing-orangedata', $record);
        }
        var_dump($response);
    }
}
