<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;

class WorkerStartedEvent
{
    public function __construct(public Application $app)
    {
    }
}
