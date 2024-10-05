<?php

namespace RrParallel\Listeners;

use RrParallel\Events\JobWaitingErrorEvent;

class JobWaitingErrorListener
{
    public function handle(JobWaitingErrorEvent $event): void
    {
        report($event->exception);
    }
}
