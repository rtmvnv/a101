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
        $mongo = (new \MongoDB\Client(
            'mongodb://' . config('services.mongo.server'),
            [
                'username' => config('services.mongo.username'),
                'password' => config('services.mongo.password'),
                'authSource' => config('services.mongo.auth_source'),
            ],
            [
                'typeMap' => [
                    'array' => 'array',
                    'document' => 'array',
                    'root' => 'array',
                ],
            ],
        ))->a101->events;

        $mongo->insertOne(['test' => 'test']);

        // $accrual = Accrual::where('uuid', '320ade49-5bd8-4e58-b748-7c8ddfecf3eb')->first();
        // print_r($accrual->status);
    }
}
