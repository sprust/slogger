<?php

namespace App\Services\Roadrunner;

use RoadRunner\Servers\Http\Events\RrHttpRequestHandlingErrorEvent;

class RrHttpRequestHandlingErrorListener
{
    public function handle(RrHttpRequestHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
