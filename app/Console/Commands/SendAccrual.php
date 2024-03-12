<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Faker\Generator as Faker;
use Carbon\Carbon;
use App\A101;

class SendAccrual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accrual
        {email?}
        {sum?}
        {period?}
        {account?}
        {name?}
        {attachment?}
        {--fake : Fake missing data fields instead of asking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send new accrual. Emulates a request from 1C.';

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
        $arguments = $this->validateInput();

        $this->info('REQUEST');
        $this->info('     email: ' . $arguments['email']);
        $this->info('       sum: ' . $arguments['sum'] / 100);
        $this->info('    period: ' . $arguments['periodDatetime']->translatedFormat('F Y'));
        $this->info('   account: ' . $arguments['account']);
        $this->info('      name: ' . $arguments['name']);
        $this->info('attachment: ' . $arguments['attachment']);
        $this->info('');

        $a101 = app(A101::class);
        $arguments['signature'] = $a101->postApiAccrualsSignature($arguments);

        /*
         * Use external API
         * Могут быть сложности с сертификатами HTTPS
         */
        // $client = new \GuzzleHttp\Client();
        // $response = $client->request(
        //     'POST',
        //     route('a101_accruals'),
        //     [
        //         'query' => [
        //             'sum' => $arguments['sum'],
        //             'period' => $arguments['periodDatetime']->translatedFormat('Ym'),
        //             'account' => $arguments['account'],
        //             'name' => $arguments['name'],
        //             'email' => $arguments['email'],
        //             'signature' => $arguments['signature'],
        //         ],
        //         'body' => base64_encode(file_get_contents($arguments['attachment'])),
        //         'http_errors' => false,
        //     ]
        // );

        // $this->info('RESPONSE');
        // $this->info('HTTP code: ' . $response->getStatusCode());

        // $jsonResponse = json_decode($response->getBody(), true, 512, JSON_OBJECT_AS_ARRAY);
        // if (json_last_error() === JSON_ERROR_NONE) {
        //     // В ответе пришел JSON
        //     $this->info(json_encode($jsonResponse, JSON_PRETTY_PRINT));
        //     if (isset($jsonResponse['data']['accrual_id'])) {
        //         $this->line(route('accrual', $jsonResponse['data']['accrual_id']));
        //     }
        // } else {
        //     // Ответ неструктурирован
        //     $this->info($response->getBody());
        // }

        /*
         * Use internal call
         */
        $request = new \Illuminate\Http\Request(
            [],
            [
                'sum' => $arguments['sum'],
                'period' => $arguments['periodDatetime']->translatedFormat('Ym'),
                'account' => $arguments['account'],
                'name' => $arguments['name'],
                'email' => $arguments['email'],
                'signature' => $arguments['signature'],
            ],
            [],
            [],
            [],
            [],
            base64_encode(file_get_contents($arguments['attachment'])),
        );
        $request->setMethod('POST');

        $response = $a101->postApiAccruals($request, 'a101');

        $this->info('RESPONSE');
        $jsonResponse = json_decode($response->content(), true, 512, JSON_OBJECT_AS_ARRAY);
        if (json_last_error() === JSON_ERROR_NONE) {
            // В ответе пришел JSON
            $this->info(json_encode($jsonResponse, JSON_PRETTY_PRINT));
        } else {
            // Ответ неструктурирован
            $this->info($response->content());
        }
    }

    protected function validateInput()
    {
        $arguments = $this->arguments();

        /*
         * Fake missing data fields
         */
        if ($this->option('fake') and !app()->environment('production')) {
            $faker = app(Faker::class);

            // Сумма в копейках
            if ($arguments['sum'] === null) {
                $arguments['sum'] = $faker->numberBetween(10000, 100000);
            }

            if ($arguments['period'] === null) {
                $arguments['period'] = date('Ym');
            }

            if ($arguments['account'] === null) {
                $arguments['account'] = 'БВ' . $faker->randomNumber(6, true);
            }

            if ($arguments['name'] === null) {
                $arguments['name'] = $faker->name();
            }

            if ($arguments['email'] === null) {
                $arguments['email'] = 'null@vic-insurance.ru';
            }

            if ($arguments['attachment'] === null) {
                $arguments['attachment'] = base_path('tests/Feature/XlsxToPdf.xlsx');
            }
        }

        /*
         * Validate sum
         */
        while (!filter_var($arguments['sum'], FILTER_VALIDATE_FLOAT)) {
            $arguments['sum'] = $this->ask('sum (Пример: 123.45)');
        }

        /*
         * Validate period
         */
        $arguments['periodDatetime'] = $this->stringToCarbon($arguments['period']);
        while (!is_a($arguments['periodDatetime'], Carbon::class)) {
            $string = mb_strtoupper($this->ask('period (Пример: 01.2022)'));
            $arguments['periodDatetime'] = $this->stringToCarbon($string);
        }

        /*
         * Validate account
         */
        $arguments['account'] = mb_strtoupper($arguments['account']);

        /*
         * Validate name
         */
        while (!preg_match("/^[a-zA-Z\p{Cyrillic}\s\-]+$/u", $arguments['name'])) {
            $arguments['name'] = $this->ask('ФИО');
        }

        /*
         * Validate email
         */
        while (!filter_var($arguments['email'], FILTER_VALIDATE_EMAIL)) {
            $arguments['email'] = $this->ask('email');
        }

        /*
         * Validate attachment
         */
        while (!is_readable($arguments['attachment'])) {
            echo $arguments['attachment'];
            $arguments['attachment'] = base_path($this->ask('attachment file'));
        }

        return $arguments;
    }

    protected function stringToCarbon($string)
    {
        if (empty($string)) {
            return false;
        }

        try {
            $date = Carbon::createFromFormat('m.y', $string);
            return $date;
        } catch (\Throwable $th) {
        }

        try {
            $date = Carbon::createFromFormat('Ym', $string);
            return $date;
        } catch (\Throwable $th) {
        }

        try {
            $date = Carbon::createFromFormat('m.Y', $string);
            return $date;
        } catch (\Throwable $th) {
        }

        try {
            $date = new Carbon($string);
            return $date;
        } catch (\Throwable $th) {
        }

        return false;
    }
}
