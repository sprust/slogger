<?php

namespace App\Services\SLogger;

use RrParallel\Events\JobHandledEvent;
use RrParallel\Events\JobHandlingErrorEvent;
use RrParallel\Events\JobReceivedEvent;

class EventWatcher extends \SLoggerLaravel\Watchers\Children\EventWatcher
{
    /** @var array<class-string<object>> */
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
