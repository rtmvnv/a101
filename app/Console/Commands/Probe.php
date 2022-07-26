<?php

namespace App\Console\Commands;

use App\XlsxToPdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use orangedata\orangedata_client;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use App\A101;
use App\Models\Accrual;

class Probe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'probe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Try some code during development';

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

        $some = new Some();
        var_dump($some);

        var_dump($some->func());

        return;

        $mockOrangeData = Mockery::mock(\DateTime::class)->makePartial();
        var_dump(get_class($mockOrangeData));

        $mockOrangeData->shouldReceive('send_order');
        var_dump(get_class($mockOrangeData));

        $mockOrangeData = Mockery::mock(\DateTime::class)->makePartial()->shouldReceive('send_order');
        var_dump(get_class($mockOrangeData));
    }
}

class Some
{
    function func() {
        return $this;
    }
}