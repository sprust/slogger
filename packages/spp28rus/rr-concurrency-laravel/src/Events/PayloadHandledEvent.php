<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;

class PayloadHandledEvent
{
    public function __construct(
        public Application $app,
        public string $payload,
        public mixed $result
    ) {
    }
}
