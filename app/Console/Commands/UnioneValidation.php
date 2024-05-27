<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne\UniOne;

/**
 * https://docs.unione.io/en/web-api-ref?php#email-validation-single
 */
class UnioneValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:validation {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detailed information on email validity';

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
        $email = $this->argument('email');
        $unione = app(UniOne::class);

        print_r($unione->validateEmail($email));

        $this->line(json_encode(
            $unione->request('email-validation/single.json', ["email" => $email]),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));
        return Command::SUCCESS;
    }
}
