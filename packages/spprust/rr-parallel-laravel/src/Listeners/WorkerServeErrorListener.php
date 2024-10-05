<?php

namespace RrParallel\Listeners;

use RrParallel\Events\WorkerServeErrorEvent;

class WorkerServeErrorListener
{
    public function handle(WorkerServeErrorEvent $event): void
    {
        report($event->exception);
    }
}
