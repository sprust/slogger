<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class RrHttpRequestReceivedEvent
{
    public function __construct(
        public Application $app,
        public Request $request
    ) {
    }
}
