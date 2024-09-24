<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;

class WorkerStoppingEvent
{
    public function __construct(public Application $app)
    {
    }
}
