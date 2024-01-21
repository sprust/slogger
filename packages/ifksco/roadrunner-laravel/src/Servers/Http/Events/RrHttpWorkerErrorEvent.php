<?php

namespace RoadRunner\Servers\Http\Events;

use Throwable;

class RrHttpWorkerErrorEvent
{
    public function __construct(public Throwable $exception)
    {
    }
}
