<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

class JobReceivedEvent
{
    public function __construct(
        public Application $app,
        public QueuedTaskInterface $task,
    ) {
    }
}
