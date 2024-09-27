<?php

namespace RrConcurrency\Listeners;

use RrConcurrency\Events\JobsServerErrorEvent;

class JobsServerErrorListener
{
    public function handle(JobsServerErrorEvent $event): void
    {
        report($event->exception);
    }
}
