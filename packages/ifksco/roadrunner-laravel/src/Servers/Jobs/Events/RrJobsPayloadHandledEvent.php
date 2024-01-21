<?php

namespace RoadRunner\Servers\Jobs\Events;

use Illuminate\Foundation\Application;

class RrJobsPayloadHandledEvent
{
    public function __construct(
        public Application $app,
        public string $payload,
        public mixed $result
    ) {
    }
}
