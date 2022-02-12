<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Accrual;
use App\UniOne\Message;
use App\MoneyMailRu\MoneyMailRu;
use App\A101;
use NumberFormatter;
use Illuminate\Support\Facades\Validator;

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
        try {
            //code...
            $email = 'test@test.com1';
            $result = Validator::make(['email' => $email], [
                'email' => 'bail|required|email:rfc',
            ])->errors();
            if ($result->isNotEmpty()) {
                throw new \Exception('Некорректный формат email');
            }

            $result = Validator::make(['email' => $email], [
                'email' => 'bail|required|email:rfc,dns',
            ])->errors();
            if ($result->isNotEmpty()) {
                $domainName = substr(strrchr($email, "@"), 1);
                throw new \Exception('Несуществующий домен ' . $domainName);
            }
        } catch (\Throwable $th) {
            echo 'exception:' . $th->getMessage();
        }
    }
}
