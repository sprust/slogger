<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;
use Laravel\Octane\Octane;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;

class JobReceivedEvent
{
    /**
     * for octane listener
     *
     * @see Octane::prepareApplicationForNextOperation()
     */
    public Application $sandbox;

    public function __construct(
        public Application $app,
        public QueuedTaskInterface $task,
    ) {
        $this->sandbox = $this->app;
    }
}
