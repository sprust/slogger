<?php

namespace RrConcurrency\Listeners;

use RrConcurrency\Events\PayloadHandlingErrorEvent;

class PayloadHandlingErrorListener
{
    public function handle(PayloadHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
