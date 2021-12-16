<?php

namespace App;

use App\RunIdProcessor;

class RunIdFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(new RunIdProcessor());
        }
    }
}
