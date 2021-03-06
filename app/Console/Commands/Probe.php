<?php

namespace App\Console\Commands;

use App\XlsxToPdf;
use Illuminate\Console\Command;

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
        // echo new XlsxToPdf()(file_get_contents('tests/Feature/XlsxToPdf.pdf'));
        app(XlsxToPdf::class)('test');
    }
}
