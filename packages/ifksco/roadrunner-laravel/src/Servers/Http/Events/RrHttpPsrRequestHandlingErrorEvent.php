<?php

namespace RoadRunner\Servers\Http\Events;

use Illuminate\Foundation\Application;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RrHttpPsrRequestHandlingErrorEvent
{
    public function __construct(
        public Application $app,
        public ServerRequestInterface $request,
        public Throwable $exception
    ) {
    }
}
