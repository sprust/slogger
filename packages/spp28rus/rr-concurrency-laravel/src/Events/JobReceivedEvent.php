<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;

class JobReceivedEvent
{
    public function __construct(
        public Application $app,
        public string $payload
    ) {
    }
}
