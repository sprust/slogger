<?php

namespace RrParallel\Events;

use Illuminate\Foundation\Application;

class WorkerStoppedEvent
{
    public function __construct(public Application $app)
    {
    }
}
