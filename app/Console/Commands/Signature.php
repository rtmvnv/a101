<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\A101;

class Signature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'signature';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate A101 request signature';

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
        $a101 = new A101();
        echo $a101->postApiAccrualsSignature([
            'sum' => 100,
            'period' => '202202',
            'email' => 'null@vic-insurance.ru',
            'account' => 'ИК123456',
            'name' => 'Имя User-Name',
        ]) . PHP_EOL;

        return Command::SUCCESS;
    }
}
