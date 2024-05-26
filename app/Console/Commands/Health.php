<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\HealthCheck as HealthCheck;

class Health extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $checks = (new HealthCheck())();

        $status = 'OK';
        foreach ($checks as $check) {
            if ($check !== True) {
                $status = 'FAIL';
            }
        }

        echo json_encode($checks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
        echo $status . PHP_EOL;
        if ($status !== 'OK') {
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
