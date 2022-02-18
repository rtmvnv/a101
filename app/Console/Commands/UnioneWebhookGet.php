<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne\UniOne;

/**
 * https://docs.unione.ru/web-api-ref#webhook
 */
class UnioneWebhookGet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:webhook_get {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gets properties of a Unione webhook';

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
        if (empty($this->argument('url'))) {
            $url = route('unione');
        } else {
            $url = $this->argument('url');
        }

        $requestBody = [
            'url' => $url,
        ];
        print_r($requestBody);
        echo PHP_EOL;

        $unione = app(UniOne::class);
        print_r($unione->request('webhook/get.json', $requestBody));

        return Command::SUCCESS;
    }
}
