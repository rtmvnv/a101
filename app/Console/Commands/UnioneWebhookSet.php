<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne;

class UnioneWebhookSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:webhook_set {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Устанавливает или изменяет свойства вебхука';

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
            'status' => 'active',
            'event_format' => 'json_post',
            'delivery_info' => 1,
            'single_event' => 1,
            'max_parallel' => 5,
            'events' => [
                'spam_block' => [
                    '*'
                ],
                'email_status' => [
                    'delivered',
                    'opened',
                    'clicked',
                    'unsubscribed',
                    'soft_bounced',
                    'hard_bounced',
                    'spam'
                ]
            ]
        ];
        print_r($requestBody);
        echo PHP_EOL;

        $unione = app(UniOne::class);
        print_r($unione->request('webhook/set.json', $requestBody));

        return Command::SUCCESS;
    }
}
