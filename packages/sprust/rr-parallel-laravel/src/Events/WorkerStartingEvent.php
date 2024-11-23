<?php

namespace RrParallel\Events;

use Illuminate\Foundation\Application;

class WorkerStartingEvent
{
    public function __construct(public Application $app)
    {
    }
}
