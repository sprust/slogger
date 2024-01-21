<?php

namespace RoadRunner\Servers\Jobs\Events;

use Illuminate\Foundation\Application;

class RrJobsWorkerStartingEvent
{
    public function __construct(public Application $app)
    {
    }
}
