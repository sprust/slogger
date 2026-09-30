<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Infrastructure\Tasks;

use App\Modules\Trace\Domain\Actions\Mutations\RefreshTraceDataPathTypesAction;
use App\Modules\Trace\Infrastructure\Tasks\RefreshTraceDataPathTypesTask;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SConcur\Laravel\Tasks\TickResultEnum;

class RefreshTraceDataPathTypesTaskTest extends TestCase
{
    public function testRefreshesAtStartThenWaitsForTheInterval(): void
    {
        $action = $this->createMock(RefreshTraceDataPathTypesAction::class);
        $action->expects($this->once())->method('handle')->willReturn(2);

        $task = new RefreshTraceDataPathTypesTask($action);

        $this->assertSame(TickResultEnum::Worked, $task->tick());
        $this->assertSame(TickResultEnum::Idle, $task->tick());
    }

    public function testAFailedRefreshIsTriedAgainOnTheNextTick(): void
    {
        $action = $this->createMock(RefreshTraceDataPathTypesAction::class);
        $action->expects($this->exactly(2))
            ->method('handle')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new RuntimeException('ClickHouse is unreachable')),
                1
            );

        $task = new RefreshTraceDataPathTypesTask($action);

        try {
            $task->tick();
            $this->fail('No exception');
        } catch (RuntimeException) {
        }

        $this->assertSame(TickResultEnum::Worked, $task->tick());
    }
}
