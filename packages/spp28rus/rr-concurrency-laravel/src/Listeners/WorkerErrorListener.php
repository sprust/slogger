<?php

namespace RrConcurrency\Listeners;

use RrConcurrency\Events\WorkerErrorEvent;

class WorkerErrorListener
{
    public function handle(WorkerErrorEvent $event): void
    {
        report($event->exception);
    }
}
