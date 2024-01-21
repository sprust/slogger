<?php

namespace RoadRunner\Servers\Jobs\Events;

use Illuminate\Foundation\Application;
use Throwable;

class RrJobsServerErrorEvent
{
    public function __construct(
        public Application $app,
        public Throwable $exception
    ) {
    }
}
