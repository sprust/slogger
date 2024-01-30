<?php

namespace App\Services\Roadrunner;

use RoadRunner\Servers\Jobs\Events\RrJobsPayloadHandlingErrorEvent;

class RrJobsWorkerErrorListener
{
    public function handle(RrJobsPayloadHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
