<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;

class RrHttpWorkerStartingEvent
{
    public function __construct(public Application $app)
    {
    }
}
