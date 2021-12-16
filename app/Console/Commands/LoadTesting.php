<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\A101;

class LoadTesting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loadtesting {url=http://10.75.0.5/api/a101/accruals}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run load testing';

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
        $body = [
            'sum' => 100,
            'period' => '202111',
            'email' => 'test@example.com',
            'account' => 'ИК123456',
            'name' => 'Имя User-Name',
        ];

        $a101 = new A101();
        $signature = $a101->postApiAccrualsSignature($body);
        $body['signature'] = $signature;

        $client = new Client();
        try {
            $response = $client->request(
                'POST',
                $this->argument('url'),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $body,
                    'http_errors' => false,
                ]
            );

            echo $response->getStatusCode() . PHP_EOL;
        } catch (\Throwable $th) {
            echo 'exception ' . $th->getMessage() . PHP_EOL;
        }

        return Command::SUCCESS;
    }
}
