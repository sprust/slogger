<?php

namespace RrConcurrency\Events;

use Throwable;

class WorkerErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
