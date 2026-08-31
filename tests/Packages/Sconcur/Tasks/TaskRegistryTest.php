<?php

namespace Tests\Packages\Sconcur\Tasks;

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Laravel\Tasks\TaskRegistry;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * The registry is the only place a typo in the task list can be caught, and the only
 * thing that makes restarting a task mean anything: a task keeps state between ticks, so
 * a fresh instance is the whole of the restart.
 */
class TaskRegistryTest extends TestCase
{
    public function testATaskIsKeptBetweenTicksAndRebuiltOnlyOnForget(): void
    {
        $registry = $this->registry([['name' => 'a', 'task' => CountingTask::class]]);

        $first = $registry->task('a');

        $this->assertSame($first, $registry->task('a'));

        $registry->forget('a');

        $this->assertNotSame($first, $registry->task('a'));
    }

    public function testIntervalsFallBackToSafeDefaults(): void
    {
        $definition = $this->registry([['name' => 'a', 'task' => CountingTask::class]])->definition('a');

        // A list entry with no numbers polls once a second and backs off three, rather
        // than spinning the pool at full speed.
        $this->assertSame(1.0, $definition->intervalFor(TickResultEnum::Idle));
        $this->assertSame(0.0, $definition->intervalFor(TickResultEnum::Worked));
        $this->assertSame(3.0, $definition->intervalFor(TickResultEnum::Failed));
    }

    public function testEachOutcomeTakesItsOwnInterval(): void
    {
        $definition = $this->registry([
            ['name' => 'a', 'task' => CountingTask::class, 'idle' => 5, 'busy' => 0, 'backoff' => 7],
        ])->definition('a');

        $this->assertSame(5.0, $definition->intervalFor(TickResultEnum::Idle));
        $this->assertSame(0.0, $definition->intervalFor(TickResultEnum::Worked));
        $this->assertSame(7.0, $definition->intervalFor(TickResultEnum::Failed));
    }

    public function testTwoTasksUnderOneNameAreRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/two tasks named a/');

        // Silently keeping one of them would leave the other unaddressable by the
        // control commands and unexplainably absent from the pool.
        $this->registry([
            ['name' => 'a', 'task' => CountingTask::class],
            ['name' => 'a', 'task' => CountingTask::class],
        ]);
    }

    public function testAClassThatIsNotATaskIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must name a class implementing/');

        $this->registry([['name' => 'a', 'task' => self::class]]);
    }

    public function testAnEntryWithoutANameIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/requires a "name"/');

        $this->registry([['task' => CountingTask::class]]);
    }

    /**
     * @param array<int, mixed> $list
     */
    private function registry(array $list): TaskRegistry
    {
        return new TaskRegistry(new Container(), $list);
    }
}
