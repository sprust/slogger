<?php

namespace RoadRunner\Servers\Jobs\Events;

use Throwable;

class RrJobsWorkerErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
