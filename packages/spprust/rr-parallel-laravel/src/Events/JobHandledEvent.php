<?php

namespace RrParallel\Events;

use Illuminate\Foundation\Application;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

class JobHandledEvent
{
    public function __construct(
        public Application $app,
        public QueuedTaskInterface $task,
        public mixed $result
    ) {
    }
}
