<?php

namespace RoadRunner\Servers\Jobs\Events;

use Illuminate\Foundation\Application;

class RrJobsWorkerStoppingEvent
{
    public function __construct(public Application $app)
    {
    }
}
