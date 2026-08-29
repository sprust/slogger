<?php

namespace Tests\Packages\Sconcur\Tasks;

use Closure;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\Control\ControlChannel;
use SConcur\Laravel\Tasks\CooperativeSleeper;
use SConcur\Laravel\Tasks\TaskPool;
use SConcur\Laravel\Tasks\TaskPoolLogger;
use SConcur\Laravel\Tasks\TaskPoolOptions;
use SConcur\Laravel\Tasks\TaskPoolState;
use SConcur\Laravel\Tasks\TaskRegistry;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * The loop the pool runs on behalf of every task. It is exercised directly, without a
 * WaitGroup: outside a fiber the cooperative pause falls through to usleep and the
 * scheduler switch is a no-op, so the loop is ordinary synchronous code.
 */
class TaskPoolLoopTest extends TestCase
{
    private const string NAME = 'counting';

    public function testItKeepsTickingUntilTheTaskIsDeactivated(): void
    {
        $state = new TaskPoolState([self::NAME]);
        $task  = $this->task($state, stopAfter: 3);

        $this->runLoop($state, $this->registry($this->container($task)));

        $this->assertSame(3, $task->ticks);
    }

    public function testTheLoopMarksTheTaskStoppedWhenItLeaves(): void
    {
        $state = new TaskPoolState([self::NAME]);

        $this->runLoop($state, $this->registry($this->container($this->task($state, stopAfter: 1))));

        // This is what the shutdown waits on; a loop that left without saying so would
        // hold the pool until the hard deadline.
        $this->assertSame([], $state->runningNames());
    }

    public function testAThrowingTickIsReportedAndTheLoopCarriesOn(): void
    {
        $state = new TaskPoolState([self::NAME]);
        $task  = $this->task($state, stopAfter: 2);

        $task->throws = true;

        $exceptions = $this->createMock(ExceptionHandler::class);
        $exceptions->expects($this->exactly(2))->method('report');

        // WaitGroup::iterate() rethrows the first exception any member raises and stops
        // the group on the way out, so a tick allowed to propagate would take every
        // other task of the pool down with it.
        $this->runLoop($state, $this->registry($this->container($task)), $exceptions);

        $this->assertSame(2, $task->ticks);
    }

    public function testARelaunchRequestRebuildsTheTaskBetweenTicks(): void
    {
        $state = new TaskPoolState([self::NAME]);
        $first = new CountingTask();

        $first->onTick = static function () use ($state): void {
            $state->requestRelaunch(self::NAME);
        };

        // Whatever the container builds next ends the loop, so the assertion is about
        // which instance ran, not about how long it ran.
        $container = new Container();
        $container->instance(CountingTask::class, $first);

        $registry = $this->registry($container);
        $registry->task(self::NAME);

        $container->bind(CountingTask::class, fn(): CountingTask => $this->task($state, stopAfter: 1));

        $this->runLoop($state, $registry);

        $this->assertSame(1, $first->ticks);
        $this->assertNotSame($first, $registry->task(self::NAME));
    }

    public function testAnIdleTickIsFollowedByTheIdleInterval(): void
    {
        $state = new TaskPoolState([self::NAME]);
        $task  = $this->task($state, stopAfter: 2);

        $task->results = [TickResultEnum::Idle];

        $started = microtime(true);

        $this->runLoop($state, $this->registry($this->container($task), idle: 0.05));

        $this->assertSame(2, $task->ticks);
        $this->assertGreaterThanOrEqual(0.05, microtime(true) - $started);
    }

    public function testADeactivatedTaskDoesNotWaitOutItsInterval(): void
    {
        $state = new TaskPoolState([self::NAME]);

        $started = microtime(true);

        // A second of idle interval is a second the shutdown would otherwise wait for,
        // once per sleeping task; the pause ends the moment the task is deactivated.
        $this->runLoop($state, $this->registry($this->container($this->task($state, stopAfter: 1)), idle: 5));

        $this->assertLessThan(1, microtime(true) - $started);
    }

    /**
     * A task that ends the loop after a set number of ticks. The count is also the
     * suite's guard: a loop that stops reading the state would otherwise hang it.
     */
    private function task(TaskPoolState $state, int $stopAfter): CountingTask
    {
        $task = new CountingTask();

        $task->onTick = static function (int $tick) use ($state, $stopAfter): void {
            if ($tick >= $stopAfter) {
                $state->deactivate(self::NAME);
            }
        };

        return $task;
    }

    private function container(CountingTask $task): Container
    {
        $container = new Container();
        $container->instance(CountingTask::class, $task);

        return $container;
    }

    private function registry(Container $container, float $idle = 0): TaskRegistry
    {
        return new TaskRegistry($container, [
            [
                'name'    => self::NAME,
                'task'    => CountingTask::class,
                'idle'    => $idle,
                'busy'    => 0,
                'backoff' => 0,
            ],
        ]);
    }

    private function runLoop(
        TaskPoolState $state,
        TaskRegistry $registry,
        ?ExceptionHandler $exceptions = null,
    ): void {
        $pool = new LoopTaskPool(
            registry: $registry,
            channel: new ControlChannel(new Repository(new ArrayStore()), 'tasks:control'),
            sleeper: new CooperativeSleeper(10),
            options: TaskPoolOptions::fromArray([]),
            logger: new TaskPoolLogger('/dev/null'),
            exceptions: $exceptions ?? $this->createMock(ExceptionHandler::class),
        );

        ($pool->exposeLoop($state, self::NAME))();
    }
}

/** Opens the loop for direct use; it is what the pool hands to WaitGroup::add(). */
class LoopTaskPool extends TaskPool
{
    public function exposeLoop(TaskPoolState $state, string $name): Closure
    {
        return $this->loop($state, $name);
    }
}
