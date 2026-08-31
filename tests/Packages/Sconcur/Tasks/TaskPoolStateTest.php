<?php

namespace Tests\Packages\Sconcur\Tasks;

use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\TaskPoolState;

/**
 * The state is the only channel between the controller and the tasks, because there is
 * no other one: nothing can reach into another coroutine. Everything a stop or a restart
 * means has to be expressible here and readable between two ticks.
 */
class TaskPoolStateTest extends TestCase
{
    public function testStoppingOneTaskLeavesTheOthersTicking(): void
    {
        $state = new TaskPoolState(['cron', 'indexes']);

        $state->deactivate('cron');

        $this->assertFalse($state->isActive('cron'));
        $this->assertTrue($state->isActive('indexes'));
        $this->assertFalse($state->isStopRequested());
    }

    public function testStoppingThePoolDeactivatesEveryTask(): void
    {
        $state = new TaskPoolState(['cron', 'indexes']);

        $state->stopAll();

        $this->assertTrue($state->isStopRequested());
        $this->assertFalse($state->isActive('cron'));
        $this->assertFalse($state->isActive('indexes'));
    }

    public function testATaskCountsAsRunningUntilItsLoopSaysOtherwise(): void
    {
        $state = new TaskPoolState(['cron', 'indexes']);

        // Deactivating only asks; the loop is still inside its tick, and the shutdown
        // has to wait for that, not for the request.
        $state->stopAll();

        $this->assertSame(['cron', 'indexes'], $state->runningNames());

        $state->markStopped('cron');

        $this->assertSame(['indexes'], $state->runningNames());
    }

    public function testARelaunchRequestIsHandedOverOnce(): void
    {
        $state = new TaskPoolState(['cron']);

        $state->requestRelaunch('cron');

        $this->assertTrue($state->takeRelaunch('cron'));
        $this->assertFalse($state->takeRelaunch('cron'));
    }

    public function testAPauseIsInterruptedByAStopAndByARelaunch(): void
    {
        $state     = new TaskPoolState(['cron']);
        $interrupt = $state->interruptFor('cron');

        $this->assertFalse($interrupt());

        $state->requestRelaunch('cron');

        // Without this a restart would wait out the task's whole idle interval, and a
        // stop would wait it out for every sleeping task at once.
        $this->assertTrue($interrupt());

        $state->takeRelaunch('cron');
        $state->deactivate('cron');

        $this->assertTrue($interrupt());
    }
}
