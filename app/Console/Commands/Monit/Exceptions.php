<?php

namespace App\Console\Commands\Monit;

use Illuminate\Console\Command;
use MongoDB\BSON\UTCDateTime;

class Exceptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monit:exceptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reports the number of exceptions registered recently';

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
        try {
            $events = app('mongo_events');

            $query = [
                'context.response.exception.message' => [ '$exists' => true ],
                'datetime' => array('$gte' => new UTCDateTime(1000 * strtotime('-1 minutes'))),
            ];

            $count = $events->count($query);
            if ($count > 0) {
                echo ($events->findOne($query))['context']['response']['exception']['message'];
            } else {
                echo 'No exceptions';
            }

            return $count;
        } catch (\Throwable $th) {
            echo $th->getMessage() . ' [' . $th->getFile() . ':' . $th->getLine() . ']';
            return 1;
        }
    }
}
