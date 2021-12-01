<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Accrual;
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
        $accrual = Accrual::where('uuid', '320ade49-5bd8-4e58-b748-7c8ddfecf3eb')->first();

        print_r($accrual->status);

        // $data = file_get_contents(storage_path('file 30.11.2021 09-30-34.pdf'));
        // $data = str_replace(array("\r", "\n"), '', $data);

        // $decodedData = base64_decode($data, true);
        // $encodedData = base64_encode($decodedData);

        // echo 'ORIGINAL:' . PHP_EOL;
        // echo $data . PHP_EOL . PHP_EOL;
        // echo 'ENCODED:' . PHP_EOL;
        // echo $encodedData;

        // if (base64_encode(base64_decode($data, true)) === $data) {
        //     echo '$data is valid' . PHP_EOL;
        // } else {
        //     echo '$data is NOT valid' . PHP_EOL;
        // }

        // $message = new UniOneMessage();
        // $message->to('aivanov@vic-insurance.ru', 'No Name');
        // $message->subject('subject');
        // $message->plain('body');
        // $message->addAttachment('application/pdf', 'file.pdf', $data);

        // $result = $message->send();

        // print_r($result);
    }
}
