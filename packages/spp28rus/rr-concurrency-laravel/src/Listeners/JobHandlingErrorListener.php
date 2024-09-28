<?php

namespace RrConcurrency\Listeners;

use RrConcurrency\Events\JobHandlingErrorEvent;

class JobHandlingErrorListener
{
    public function handle(JobHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
