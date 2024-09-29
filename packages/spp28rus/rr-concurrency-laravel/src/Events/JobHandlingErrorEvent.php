<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;
use Throwable;

class JobHandlingErrorEvent
{
    public function __construct(
        public Application $app,
        public string $taskId,
        public string $payload,
        public Throwable $exception
    ) {
    }
}
