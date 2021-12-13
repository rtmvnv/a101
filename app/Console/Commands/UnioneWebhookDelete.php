<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne;

class UnioneWebhookDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:webhook_delete {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $requestBody = [
            'url' => $this->argument('url'),
        ];
        print_r($requestBody);
        echo PHP_EOL;

        $unione = app(UniOne::class);
        print_r($unione->request('webhook/delete.json', $requestBody));

        return Command::SUCCESS;
    }
}
