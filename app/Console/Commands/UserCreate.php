<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class UserCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add the "a101" user';

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
        if (empty($this->argument('username'))) {
            $username = $this->ask('username');
        } else {
            $username = $this->argument('username');
        }

        $password = $this->secret('password');

        User::create(['username' => $username, 'password' => $password]);

        return 0;
    }
}
