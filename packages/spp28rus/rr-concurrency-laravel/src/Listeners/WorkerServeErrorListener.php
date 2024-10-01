<?php

namespace RrConcurrency\Listeners;

use RrConcurrency\Events\WorkerServeErrorEvent;

class WorkerServeErrorListener
{
    public function handle(WorkerServeErrorEvent $event): void
    {
        report($event->exception);
    }
}
