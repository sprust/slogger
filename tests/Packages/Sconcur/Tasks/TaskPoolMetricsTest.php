<?php

namespace Tests\Packages\Sconcur\Tasks;

use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\TaskPoolMetrics;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * The pool's tick counters, shaped as the panel's delivery section so its existing
 * columns render them without knowing what a task is.
 */
class TaskPoolMetricsTest extends TestCase
{
    public function testAFinishedTickCountsAsHandledAndLeavesTheAir(): void
    {
        $metrics = new TaskPoolMetrics(1);

        $metrics->tickStarted('cron');
        $metrics->tickFinished('cron', TickResultEnum::Worked);

        $section = $metrics->section();

        $this->assertSame(1, $section['delivered']);
        $this->assertSame(1, $section['acked']);
        $this->assertSame(0, $section['refused']);
        $this->assertSame(0, $section['inFlight']);
    }

    public function testAFailedTickCountsAsRefused(): void
    {
        $metrics = new TaskPoolMetrics(1);

        $metrics->tickStarted('cron');
        $metrics->tickFinished('cron', TickResultEnum::Failed);

        $section = $metrics->section();

        $this->assertSame(0, $section['acked']);
        $this->assertSame(1, $section['refused']);

        // Both outcomes are timed: a tick that threw still took the time it took.
        $this->assertSame(1, $section['timed']);
    }

    public function testATickStillRunningIsInFlight(): void
    {
        $metrics = new TaskPoolMetrics(2);

        $metrics->tickStarted('cron');
        $metrics->tickStarted('indexes');
        $metrics->tickFinished('cron', TickResultEnum::Worked);

        $section = $metrics->section();

        // Only the finished one is a unit of work so far; the other is still running.
        $this->assertSame(1, $section['delivered']);
        $this->assertSame(1, $section['inFlight']);

        // Capacity is the number of tasks — one coroutine each, the same meaning the
        // consumer pool gives the field.
        $this->assertSame(2, $section['coroutines']);
    }

    public function testAnIdleTickIsNotCountedAsWork(): void
    {
        $metrics = new TaskPoolMetrics(1);

        // A task polling every second for work that is not there is the consumer's empty
        // wait. Counted, Handled would just be a measure of the poll interval, and the
        // average duration would be the cost of an empty poll rather than of the work.
        $metrics->tickStarted('indexes');
        $metrics->tickFinished('indexes', TickResultEnum::Idle);

        $section = $metrics->section();

        $this->assertSame(0, $section['delivered']);
        $this->assertSame(0, $section['acked']);
        $this->assertSame(0, $section['timed']);
        $this->assertSame(0, $section['inFlight']);
    }

    public function testAverageDurationIsZeroUntilSomethingFinishes(): void
    {
        $metrics = new TaskPoolMetrics(1);

        $metrics->tickStarted('cron');

        // Nothing measured yet: dividing by no samples would be a zero of a different
        // kind, and the panel weights this average by `timed`.
        $this->assertSame(0.0, $metrics->section()['avgMs']);
        $this->assertSame(0, $metrics->section()['timed']);
    }

    public function testAgeBucketsDoNotOverlapAndStartEmpty(): void
    {
        $metrics = new TaskPoolMetrics(1);

        $metrics->tickStarted('cron');

        $section = $metrics->section();

        // A tick that just began is younger than a second, so it is in none of them.
        $this->assertSame(0, $section['inFlight1to5s']);
        $this->assertSame(0, $section['inFlight5to15s']);
        $this->assertSame(0, $section['inFlightOver15s']);
        $this->assertSame(1, $section['inFlight']);
    }
}
