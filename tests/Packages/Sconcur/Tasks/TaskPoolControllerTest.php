<?php

namespace Tests\Packages\Sconcur\Tasks;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\Control\ControlActionEnum;
use SConcur\Laravel\Tasks\Control\ControlChannel;
use SConcur\Laravel\Tasks\CooperativeSleeper;
use SConcur\Laravel\Tasks\TaskPoolController;
use SConcur\Laravel\Tasks\TaskPoolLogger;
use SConcur\Laravel\Tasks\TaskPoolOptions;
use SConcur\Laravel\Tasks\TaskPoolState;
use SConcur\Laravel\Tasks\TaskRegistry;
use SConcur\WaitGroup;

/**
 * The controller's decisions, taken one pass at a time.
 *
 * Every case here starts with the tasks already marked stopped, so run() makes exactly
 * one pass and returns: it reads the signal, then the channel, then the memory, then
 * finds nothing left running. What each input did is then visible in the state.
 */
class TaskPoolControllerTest extends TestCase
{
    public function testASignalStopsThePool(): void
    {
        $state      = $this->drainedState();
        $controller = $this->controller($state, $this->channel());

        $controller->signalled();
        $controller->run(WaitGroup::create());

        $this->assertTrue($state->isStopRequested());
        $this->assertNotNull($state->hardDeadlineAt());
    }

    public function testACommandOlderThanTheProcessIsIgnored(): void
    {
        $channel = $this->channel();

        // Posted before this pool existed: it is the stop that ended the previous one,
        // and acting on it would stop every process the supervisor starts in its place.
        $channel->send(ControlActionEnum::Stop);

        $state = $this->drainedState();

        $this->controller($state, $channel)->run(WaitGroup::create());

        $this->assertFalse($state->isStopRequested());
    }

    public function testAStopForOneTaskLeavesTheRestOfThePoolAlone(): void
    {
        $state   = $this->drainedState();
        $channel = $this->channel();

        $channel->send(ControlActionEnum::Stop, 'cron');

        $this->controller($state, $channel)->run(WaitGroup::create());

        $this->assertFalse($state->isActive('cron'));
        $this->assertTrue($state->isActive('indexes'));
        $this->assertFalse($state->isStopRequested());
    }

    public function testAStopWithoutATargetStopsThePool(): void
    {
        $state   = $this->drainedState();
        $channel = $this->channel();

        $channel->send(ControlActionEnum::Stop);

        $this->controller($state, $channel)->run(WaitGroup::create());

        $this->assertTrue($state->isStopRequested());
    }

    public function testARestartAsksForANewInstanceAndNothingElse(): void
    {
        $state   = $this->drainedState();
        $channel = $this->channel();

        $channel->send(ControlActionEnum::Restart, 'cron');

        $this->controller($state, $channel)->run(WaitGroup::create());

        $this->assertTrue($state->takeRelaunch('cron'));
        $this->assertFalse($state->takeRelaunch('indexes'));
        $this->assertTrue($state->isActive('cron'));
    }

    public function testACommandForAnUnknownTaskChangesNothing(): void
    {
        $state   = $this->drainedState();
        $channel = $this->channel();

        $channel->send(ControlActionEnum::Stop, 'nonesuch');

        $this->controller($state, $channel)->run(WaitGroup::create());

        $this->assertTrue($state->isActive('cron'));
        $this->assertTrue($state->isActive('indexes'));
        $this->assertFalse($state->isStopRequested());
    }

    public function testLosingTheMasterStopsThePool(): void
    {
        $state = $this->drainedState();

        // A pid that is certainly not this process's parent stands in for a master that
        // died: the kernel reparents the orphan, so getppid() stops matching and the
        // pool would otherwise keep ticking with nobody supervising it.
        $this->controller($state, $this->channel(), masterPid: PHP_INT_MAX)->run(WaitGroup::create());

        $this->assertTrue($state->isStopRequested());
    }

    public function testWithoutAMasterThePoolIgnoresTheCheck(): void
    {
        $state = $this->drainedState();

        // Standalone: no --masterPid was passed, so there is no parent to lose.
        $this->controller($state, $this->channel())->run(WaitGroup::create());

        $this->assertFalse($state->isStopRequested());
    }

    public function testPassingTheMemoryLimitStopsThePool(): void
    {
        $state = $this->drainedState();

        // A leak in one task takes the process down with it, so the limit belongs to
        // the pool and passing it goes through the same graceful stop as a signal.
        $this->controller($state, $this->channel(), memoryMb: 0)->run(WaitGroup::create());

        $this->assertTrue($state->isStopRequested());
    }

    /** Both tasks are configured, and neither is inside a tick any more. */
    private function drainedState(): TaskPoolState
    {
        $state = new TaskPoolState(['cron', 'indexes']);

        $state->markStopped('cron');
        $state->markStopped('indexes');

        return $state;
    }

    private function channel(): ControlChannel
    {
        return new ControlChannel(new Repository(new ArrayStore()), 'tasks:control');
    }

    private function controller(
        TaskPoolState $state,
        ControlChannel $channel,
        int $memoryMb = 4096,
        int $masterPid = 0,
    ): TaskPoolController {
        return new TaskPoolController(
            state: $state,
            registry: new TaskRegistry(new Container(), [
                ['name' => 'cron', 'task' => CountingTask::class],
                ['name' => 'indexes', 'task' => CountingTask::class],
            ]),
            channel: $channel,
            sleeper: new CooperativeSleeper(1),
            options: TaskPoolOptions::fromArray(['memory_mb' => $memoryMb]),
            logger: new TaskPoolLogger('/dev/null'),
            masterPid: $masterPid,
        );
    }
}
