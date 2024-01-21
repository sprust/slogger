<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Throwable;

class RrHttpRequestHandlingErrorEvent
{
    public function __construct(
        public Application $app,
        public Request $request,
        public Throwable $exception
    ) {
    }
}
