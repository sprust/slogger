<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;
use Throwable;

class RrHttpServerErrorEvent
{
    public function __construct(
        public Application $app,
        public Throwable $exception
    ) {
    }
}
