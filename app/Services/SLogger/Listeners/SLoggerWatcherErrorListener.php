<?php

namespace App\Services\SLogger\Listeners;

use SLoggerLaravel\Events\SLoggerWatcherErrorEvent;

class SLoggerWatcherErrorListener
{
    public function handle(SLoggerWatcherErrorEvent $event): void
    {
        report($event->exception);
    }
}
