<?php

namespace RoadRunner\Servers\Jobs\Events;

use Illuminate\Foundation\Application;

class RrJobsPayloadReceivedEvent
{
    public function __construct(
        public Application $app,
        public string $payload
    ) {
    }
}
