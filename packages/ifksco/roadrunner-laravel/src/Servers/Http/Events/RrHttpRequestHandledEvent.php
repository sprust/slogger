<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RrHttpRequestHandledEvent
{
    public function __construct(
        public Application $app,
        public Request $request,
        public Response $response
    ) {
    }
}
