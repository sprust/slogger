<?php

namespace App\Services\Roadrunner;

use RoadRunner\Servers\Http\Events\RrHttpServerErrorEvent;

class RrHttpServerErrorListener
{
    public function handle(RrHttpServerErrorEvent $event): void
    {
        report($event->exception);
    }
}
