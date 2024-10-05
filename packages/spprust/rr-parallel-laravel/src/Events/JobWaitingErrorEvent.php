<?php

namespace RrParallel\Events;

use Throwable;

class JobWaitingErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
