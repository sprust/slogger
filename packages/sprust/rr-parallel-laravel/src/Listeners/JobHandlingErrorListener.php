<?php

namespace RrParallel\Listeners;

use RrParallel\Events\JobHandlingErrorEvent;

class JobHandlingErrorListener
{
    public function handle(JobHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
