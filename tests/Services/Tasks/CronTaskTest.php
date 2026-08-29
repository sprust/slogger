<?php

namespace Tests\Services\Tasks;

use App\Services\Tasks\CronTask;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * The task keeps one thing: the minute it last ran the schedule for. What that starts as
 * decides whether a restart re-runs a minute the process before it already served.
 */
class CronTaskTest extends TestCase
{
    /**
     * The cron command this replaced read the clock before entering its loop. Starting
     * from "no minute has run" instead would fire schedule:run for the minute already in
     * progress, so a restart at 11:00:20 would dispatch the 11:00 jobs a second time —
     * and restarts are ordinary here: a deploy, the memory limit, sconcur:tasks:restart.
     */
    public function testTheMinuteInProgressCountsAsAlreadyRun(): void
    {
        $task = new CronTask(new SilentLogger());

        $this->assertSame(
            TickResultEnum::Idle,
            $task->tick(),
            'a fresh task does not run the schedule for the minute it started in'
        );
    }

    public function testTheTaskIsNamedCron(): void
    {
        $this->assertSame('cron', new CronTask(new SilentLogger())->name());
    }
}
