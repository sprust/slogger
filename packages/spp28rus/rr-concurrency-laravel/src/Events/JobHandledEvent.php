<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;

class JobHandledEvent
{
    public function __construct(
        public Application $app,
        public string $taskId,
        public string $payload,
        public mixed $result
    ) {
    }
}
