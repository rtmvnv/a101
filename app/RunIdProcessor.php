<?php

namespace App;

class RunIdProcessor
{
    public function __invoke(array $record): array
    {
        $record['extra']['run_id'] = hash('fnv1a64', $_SERVER['REQUEST_TIME_FLOAT'] . getmypid(), false);

        return $record;
    }
}
