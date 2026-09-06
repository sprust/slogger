<?php

namespace Tests\Modules\Trace\Infrastructure\Tasks;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Entities\DynamicIndex\TraceDynamicIndexStatsObject;
use App\Modules\Trace\Infrastructure\Broadcasting\TraceDynamicIndexStatsBroadcast;
use App\Modules\Trace\Infrastructure\Tasks\PublishTraceDynamicIndexStatsTask;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * What the panel is told about the dynamic indexes, and when it is told nothing.
 *
 * The panel used to ask twice a second, from every open tab, for ever. The point of
 * moving that here is that it costs nothing while nothing is being built — so silence
 * while idle matters as much as speaking while busy.
 */
class PublishTraceDynamicIndexStatsTaskTest extends TestCase
{
    public function testAnIdlePoolSaysNothing(): void
    {
        $events = $this->createMock(Dispatcher::class);

        $events->expects($this->never())->method('dispatch');

        $task = $this->task($events, inProcessCounts: [0, 0]);

        $this->assertSame(TickResultEnum::Idle, $task->tick());
        $this->assertSame(TickResultEnum::Idle, $task->tick());
    }

    public function testWorkIsReportedWhileItLasts(): void
    {
        $events = $this->createMock(Dispatcher::class);

        $events->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->isInstanceOf(TraceDynamicIndexStatsBroadcast::class));

        $task = $this->task($events, inProcessCounts: [1, 2]);

        // Worked, so the pool comes back in a second rather than in two.
        $this->assertSame(TickResultEnum::Worked, $task->tick());
        $this->assertSame(TickResultEnum::Worked, $task->tick());
    }

    public function testTheEndOfTheWorkIsReportedOnceAndThenTheSilenceResumes(): void
    {
        $events = $this->createMock(Dispatcher::class);

        // Three frames, not four: the build, the closing frame that empties the progress
        // off the screen, and nothing at all after that.
        $events->expects($this->exactly(3))->method('dispatch');

        $task = $this->task($events, inProcessCounts: [1, 1, 0, 0, 0]);

        $this->assertSame(TickResultEnum::Worked, $task->tick());
        $this->assertSame(TickResultEnum::Worked, $task->tick());
        $this->assertSame(TickResultEnum::Idle, $task->tick());
        $this->assertSame(TickResultEnum::Idle, $task->tick());
        $this->assertSame(TickResultEnum::Idle, $task->tick());
    }

    /**
     * @param MockObject&Dispatcher $events
     * @param list<int>             $inProcessCounts one per tick
     */
    private function task(MockObject $events, array $inProcessCounts): PublishTraceDynamicIndexStatsTask
    {
        $stats = $this->createMock(FindTraceDynamicIndexStatsAction::class);

        $stats->method('handle')->willReturnOnConsecutiveCalls(
            ...array_map(
                static fn(int $count): TraceDynamicIndexStatsObject => new TraceDynamicIndexStatsObject(
                    inProcessCount: $count,
                    errorsCount: 0,
                    totalCount: 5,
                    indexesInProcess: [],
                ),
                $inProcessCounts
            )
        );

        return new PublishTraceDynamicIndexStatsTask($stats, $events);
    }
}
