<?php

declare(strict_types=1);

namespace Tests\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Entities\ProcessObject;
use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Trace\Domain\Actions\Mutations\DeletePartitionsAction;
use App\Modules\Trace\Entities\Trace\DeletedTracesObject;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClearTracesActionTest extends TestCase
{
    private ProcessRepository&MockObject $processes;

    private DeletePartitionsAction&MockObject $deletePartitions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-29 12:34:56', 'UTC'));

        $this->processes        = $this->createMock(ProcessRepository::class);
        $this->deletePartitions = $this->createMock(DeletePartitionsAction::class);

        $this->processes->method('create')->willReturn($this->process(id: 'new'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testDropsWhatEndedTheLifetimeAgo(): void
    {
        $this->deletePartitions->expects($this->once())
            ->method('handle')
            ->with($this->callback(static fn(Carbon $to): bool => $to->eq(Carbon::parse('2026-09-26 12:34:56', 'UTC'))))
            ->willReturn(new DeletedTracesObject(partitionsCount: 2, tracesCount: 30));

        $this->processes->expects($this->once())
            ->method('update')
            ->with('new', 2, 30, $this->isInstanceOf(Carbon::class), null);

        $this->action()->handle(72);
    }

    public function testRunThatDroppedNothingIsNotRecorded(): void
    {
        $this->deletePartitions->method('handle')->willReturn(new DeletedTracesObject(partitionsCount: 0, tracesCount: 0));

        $this->processes->expects($this->once())->method('deleteByProcessId')->with('new');
        $this->processes->expects($this->never())->method('update');

        $this->action()->handle(72);
    }

    public function testFailedRunKeepsWhatItDropped(): void
    {
        $exception = new ClickhouseQueryException('Code: 159. Timeout exceeded');

        $this->deletePartitions->method('handle')->willReturn(
            new DeletedTracesObject(partitionsCount: 1, tracesCount: 10, exception: $exception)
        );

        $this->processes->expects($this->once())
            ->method('update')
            ->with('new', 1, 10, $this->isInstanceOf(Carbon::class), $exception);

        $this->action()->handle(72);
    }

    public function testFreshActiveRunBlocks(): void
    {
        $this->processes->method('exists')->willReturn(
            $this->process(id: 'running', createdAt: Carbon::now()->subMinutes(10))
        );

        $this->deletePartitions->expects($this->never())->method('handle');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Clearing process already active');

        $this->action()->handle(72);
    }

    public function testDeadRunIsClosedAndDoesNotBlock(): void
    {
        $this->processes->method('exists')->willReturn(
            $this->process(id: 'dead', createdAt: Carbon::now()->subMinutes(61), collectionsCount: 1, tracesCount: 5)
        );

        $this->deletePartitions->method('handle')->willReturn(new DeletedTracesObject(partitionsCount: 1, tracesCount: 7));

        $updates = [];

        $this->processes->method('update')->willReturnCallback(
            static function (string $processId, int $collectionsCount, int $tracesCount, ?Carbon $clearedAt, mixed $exception) use (&$updates): void {
                $updates[] = [$processId, $collectionsCount, $tracesCount, $clearedAt !== null, $exception?->getMessage()];
            }
        );

        $this->action()->handle(72);

        $this->assertSame(
            [
                ['dead', 1, 5, true, 'The run did not finish within 60 minutes'],
                ['new', 1, 7, true, null],
            ],
            $updates
        );
    }

    private function action(): ClearTracesAction
    {
        return new ClearTracesAction(
            processRepository: $this->processes,
            deletePartitionsAction: $this->deletePartitions
        );
    }

    private function process(
        string $id,
        ?Carbon $createdAt = null,
        int $collectionsCount = 0,
        int $tracesCount = 0
    ): ProcessObject {
        $createdAt ??= Carbon::now();

        return new ProcessObject(
            id: $id,
            clearedCollectionsCount: $collectionsCount,
            clearedTracesCount: $tracesCount,
            error: null,
            clearedAt: null,
            createdAt: $createdAt,
            updatedAt: $createdAt
        );
    }
}
