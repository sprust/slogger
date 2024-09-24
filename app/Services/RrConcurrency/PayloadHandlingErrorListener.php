<?php

namespace App\Services\RrConcurrency;

use RrConcurrency\Events\PayloadHandlingErrorEvent;

class PayloadHandlingErrorListener
{
    public function handle(PayloadHandlingErrorEvent $event): void
    {
        report($event->exception);
    }
}
