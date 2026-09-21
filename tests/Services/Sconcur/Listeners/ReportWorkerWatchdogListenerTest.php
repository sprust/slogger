<?php

declare(strict_types=1);

namespace Tests\Services\Sconcur\Listeners;

use Illuminate\Support\Facades\Exceptions;
use RuntimeException;
use SConcur\Laravel\Servers\Events\WorkerWatchdogTriggered;
use SConcur\Worker\WatchdogEvent;
use SConcur\Worker\WatchdogEventEnum;
use Tests\TestCase;

class ReportWorkerWatchdogListenerTest extends TestCase
{
    public function testAWatchdogKillIsReported(): void
    {
        Exceptions::fake();

        event(
            new WorkerWatchdogTriggered(
                new WatchdogEvent(
                    event: WatchdogEventEnum::HeartbeatLost,
                    group: 'http',
                    slot: 1,
                    pid: 4242,
                    ageSeconds: 61.5,
                    watchdogTimeoutMs: 60000,
                )
            )
        );

        Exceptions::assertReported(
            static fn(RuntimeException $exception): bool => $exception->getMessage()
                === 'sconcur watchdog heartbeat-lost: worker 4242 of group [http] #1, silent for 61.5 s, limit 60000 ms'
        );
    }

    public function testAnEscalationWithoutAnAgeIsReported(): void
    {
        Exceptions::fake();

        event(
            new WorkerWatchdogTriggered(
                new WatchdogEvent(
                    event: WatchdogEventEnum::KillEscalated,
                    group: 'tasks',
                    slot: 0,
                    pid: 4243,
                    ageSeconds: null,
                    watchdogTimeoutMs: 60000,
                )
            )
        );

        Exceptions::assertReported(
            static fn(RuntimeException $exception): bool => $exception->getMessage()
                === 'sconcur watchdog kill-escalated: worker 4243 of group [tasks] #0, silent for unknown, limit 60000 ms'
        );
    }
}
