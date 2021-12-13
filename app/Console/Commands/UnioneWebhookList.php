<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UniOne;

class UnioneWebhookList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unione:webhook_list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Возвратить список вебхуков';

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
        print_r($unione->request('webhook/list.json', []));

        return Command::SUCCESS;
    }
}
