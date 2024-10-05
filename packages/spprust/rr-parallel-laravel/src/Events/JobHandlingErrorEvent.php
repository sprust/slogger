<?php

namespace RrParallel\Events;

use Illuminate\Foundation\Application;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;
use Throwable;

class JobHandlingErrorEvent
{
    public function __construct(
        public Application $app,
        public QueuedTaskInterface $task,
        public Throwable $exception
    ) {
    }
}
