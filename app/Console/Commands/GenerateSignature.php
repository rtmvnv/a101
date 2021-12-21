<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\A101;

class GenerateSignature extends Command
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
            'sum' => -23,
            'period' => '202111',
            'email' => ' a101@vic-insurance.ru ; null@vic-insurance.ru , aivanov@vic-insurance.ru ',
            'account' => 'ИК123456',
            'name' => 'Имя User-Name',
        ]);

        return Command::SUCCESS;
    }
}
