<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne\UniOne;

class UnioneSuppression extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:suppression {action : get | delete} {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage suppression lists.';

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
        $unione = app(UniOne::class);

        switch ($this->argument('action')) {
            case 'get':
                $requestBody = [
                    'email' => $this->argument('email'),
                    'all_projects' => true,
                ];
                $this->line(json_encode(
                    $unione->request('suppression/get.json', $requestBody),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
                return Command::SUCCESS;
                break;

            case 'delete':
                $requestBody = ['email' => $this->argument('email')];
                $this->line(json_encode(
                    $unione->request('suppression/delete.json', $requestBody),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                ));
                return Command::SUCCESS;
                break;

            default:
                $this->error('Unknown action "' . $this->argument('action') . '"');
                $this->line('');
                $this->line('Actions are:');
                $this->line('  get    - Gets a reason and date of email suppression;');
                $this->line('  delete - Deletes an email from suppression list.');
                $this->line('');
                $this->line('More information:');
                $this->line('  https://docs.unione.io/web-api-ref#suppression');
                break;
        }

        return Command::SUCCESS;
    }
}
