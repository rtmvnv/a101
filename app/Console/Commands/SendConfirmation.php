<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\A101;
use App\Models\Accrual;

class SendConfirmation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'confirmation
        {accrual : UUID of accrual}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send confirmation email.';

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
        $arguments = $this->arguments();

        $accrual = Accrual::where('uuid', $arguments['accrual'])->first();
        if (empty($accrual)) {
            return $this->error('Accrual "' . $arguments['accrual'] . '" not found');
        }

        $this->info('REQUEST');
        $this->info('     email: ' . $accrual->email);
        $this->info('       sum: ' . $accrual->sum);
        $this->info('    period: ' . $accrual->period);
        $this->info('   account: ' . $accrual->account);
        $this->info('      name: ' . $accrual->name);
        $this->info('');

        $a101 = app(A101::class);
        $a101->sendConfirmation($accrual);
    }
}
