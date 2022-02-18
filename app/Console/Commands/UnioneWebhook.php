<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne\UniOne;

/**
 * https://docs.unione.ru/web-api-ref#webhook
 */
class UnioneWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:webhook {action : list | get | set | delete} {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure notifications about email status changes or spam blocks';

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

        $unione = app(UniOne::class);

        switch ($this->argument('action')) {
            case 'list':
                $this->line('Default URL: ' . route('unione'));
                $this->line('');
                $this->line(json_encode(
                    $unione->request('webhook/list.json', []),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
                return Command::SUCCESS;
                break;

            case 'get':
                $requestBody = ['url' => $url];
                $this->line(json_encode(
                    $unione->request('webhook/get.json', $requestBody),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
                return Command::SUCCESS;
                break;

            case 'set':
                $requestBody = [
                    'url' => $url,
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
                $this->line(json_encode(
                    $unione->request('webhook/set.json', $requestBody),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
                return Command::SUCCESS;
                break;

            case 'delete':
                $requestBody = ['url' => $this->argument('url')];
                $this->line(json_encode(
                    $unione->request('webhook/delete.json', $requestBody),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
                return Command::SUCCESS;
                break;

            default:
                $this->error('Unknown action "' . $this->argument('action') . '"');
                $this->line('');
                $this->line('Actions are:');
                $this->line('  list   - List all or some webhooks (event notification handlers) of a user or a project;');
                $this->line('  get    - Gets properties of a webhook;');
                $this->line('  set    - Sets or edits a webhook, i.e. an event notification handler;');
                $this->line('  delete - Deletes an event notification handler.');
                $this->line('');
                $this->line('More information:');
                $this->line('  https://docs.unione.io/web-api-ref#webhook');
                break;
        }

        return;
    }
}
