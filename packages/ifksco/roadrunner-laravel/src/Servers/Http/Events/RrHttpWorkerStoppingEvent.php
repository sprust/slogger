<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;

class RrHttpWorkerStoppingEvent
{
    public function __construct(public Application $app)
    {
    }
}
