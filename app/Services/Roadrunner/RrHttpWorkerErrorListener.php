<?php

namespace App\Services\Roadrunner;

use RoadRunner\Servers\Http\Events\RrHttpWorkerErrorEvent;

class RrHttpWorkerErrorListener
{
    public function handle(RrHttpWorkerErrorEvent $event): void
    {
        report($event->exception);
    }
}
