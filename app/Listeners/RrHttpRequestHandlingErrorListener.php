<?php

namespace App\Listeners;

use RoadRunner\Servers\Http\Events\RrHttpRequestHandlingErrorEvent;

class RrHttpRequestHandlingErrorListener
{
    public function handle(RrHttpRequestHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
