<?php

namespace RrConcurrency\Listeners;

use RrConcurrency\Events\JobWaitingErrorEvent;

class JobWaitingErrorListener
{
    public function handle(JobWaitingErrorEvent $event): void
    {
        report($event->exception);
    }
}
