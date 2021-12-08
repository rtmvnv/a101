<?php

namespace App;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\ProcessIdProcessor;
use App\RunIdProcessor;

class RunIdFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            // $handler->pushProcessor(new ProcessIdProcessor());
            // $handler->pushProcessor(new IntrospectionProcessor(Logger::DEBUG, array('Illuminate\\')));
            // $handler->pushProcessor(new WebProcessor());
            $handler->pushProcessor(new RunIdProcessor());
        }
    }
}
