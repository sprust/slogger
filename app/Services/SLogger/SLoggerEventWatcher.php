<?php

namespace App\Services\SLogger;

use RrConcurrency\Events\JobHandledEvent;
use RrConcurrency\Events\JobHandlingErrorEvent;
use RrConcurrency\Events\JobReceivedEvent;

class SLoggerEventWatcher extends \SLoggerLaravel\Watchers\Services\SLoggerEventWatcher
{
    private array $ignoredEvents = [
        JobReceivedEvent::class,
        JobHandlingErrorEvent::class,
        JobHandledEvent::class,
    ];

    protected function shouldIgnore(string $eventName): bool
    {
        if (in_array($eventName, $this->ignoredEvents)) {
            return true;
        }

        return parent::shouldIgnore($eventName);
    }
}
