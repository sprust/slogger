<?php

namespace App\Services\Roadrunner;

use RoadRunner\Servers\Http\Events\RrHttpPsrRequestHandlingErrorEvent;

class RrHttpPsrRequestHandlingErrorListener
{
    public function handle(RrHttpPsrRequestHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
