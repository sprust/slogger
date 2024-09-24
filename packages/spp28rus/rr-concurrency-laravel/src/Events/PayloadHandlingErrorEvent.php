<?php

namespace RrConcurrency\Events;

use Illuminate\Foundation\Application;
use Throwable;

class PayloadHandlingErrorEvent
{
    public function __construct(
        public Application $app,
        public string $payload,
        public Throwable $exception
    ) {
    }
}
