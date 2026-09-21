<?php

declare(strict_types=1);

namespace App\Services\Sconcur\Listeners;

use RuntimeException;
use SConcur\Laravel\Servers\Events\WorkerWatchdogTriggered;

/**
 * Sends a watchdog kill to the error log. Runs in the master's supervision tick, so it stays synchronous and short.
 */
class ReportWorkerWatchdogListener
{
    public function handle(WorkerWatchdogTriggered $event): void
    {
        $watchdogEvent = $event->watchdogEvent;

        report(
            new RuntimeException(
                sprintf(
                    'sconcur watchdog %s: worker %d of group [%s] #%d, silent for %s, limit %d ms',
                    $watchdogEvent->event->value,
                    $watchdogEvent->pid,
                    $watchdogEvent->group,
                    $watchdogEvent->slot,
                    $watchdogEvent->ageSeconds === null ? 'unknown' : sprintf('%.1f s', $watchdogEvent->ageSeconds),
                    $watchdogEvent->watchdogTimeoutMs,
                )
            )
        );
    }
}
