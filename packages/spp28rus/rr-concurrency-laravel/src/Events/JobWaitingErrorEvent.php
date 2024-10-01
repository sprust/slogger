<?php

namespace RrConcurrency\Events;

use Throwable;

class JobWaitingErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
