<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;

class WorkerStoppedEvent
{
    public function __construct(public Application $app)
    {
    }
}
