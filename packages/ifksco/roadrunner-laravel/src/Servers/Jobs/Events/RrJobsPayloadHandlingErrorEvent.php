<?php

namespace RoadRunner\Servers\Jobs\Events;

use Illuminate\Foundation\Application;
use Throwable;

class RrJobsPayloadHandlingErrorEvent
{
    public function __construct(
        public Application $app,
        public string $payload,
        public Throwable $exception
    ) {
    }
}
