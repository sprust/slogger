<?php

namespace RrParallel\Events;

use Illuminate\Foundation\Application;
use Throwable;

class WorkerServeErrorEvent
{
    public function __construct(
        public Application $app,
        public Throwable $exception
    ) {
    }
}
