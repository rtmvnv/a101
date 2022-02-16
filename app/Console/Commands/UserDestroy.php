<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class UserDestroy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:destroy {username?}';

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

        $user = User::where(['username' => $username])->first();
        if (empty($user)) {
            $this->error('User "' . $username . '" not found.');
            return 0;
        }
        User::destroy($user->id);

        return 0;
    }
}
