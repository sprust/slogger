<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Mutations\DeletePartitionsAction;
use App\Modules\Trace\Repositories\Dto\Trace\Partition\TracePartitionDto;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DeletePartitionsActionTest extends TestCase
{
    public function testCountsTheDroppedHours(): void
    {
        $to = Carbon::parse('2026-09-29 12:00:00', 'UTC');

        $repository = $this->createMock(TraceRepository::class);
        $repository->expects($this->once())->method('findEndedPartitions')->with($to)->willReturn([
            new TracePartitionDto(id: '1790676000', rowsCount: 10),
            new TracePartitionDto(id: '1790679600', rowsCount: 5),
        ]);
        $repository->expects($this->exactly(2))->method('dropPartition');

        $deleted = new DeletePartitionsAction($repository)->handle($to);

        $this->assertSame(2, $deleted->partitionsCount);
        $this->assertSame(15, $deleted->tracesCount);
        $this->assertNull($deleted->exception);
    }

    public function testFailureKeepsTheHoursDroppedBeforeIt(): void
    {
        $failure = new ClickhouseQueryException('Code: 159. Timeout exceeded');

        $repository = $this->createMock(TraceRepository::class);
        $repository->method('findEndedPartitions')->willReturn([
            new TracePartitionDto(id: '1790676000', rowsCount: 10),
            new TracePartitionDto(id: '1790679600', rowsCount: 5),
            new TracePartitionDto(id: '1790683200', rowsCount: 7),
        ]);
        $repository->method('dropPartition')->willReturnCallback(
            static function (string $partitionId) use ($failure): void {
                if ($partitionId === '1790679600') {
                    throw $failure;
                }
            }
        );

        $deleted = new DeletePartitionsAction($repository)->handle(Carbon::parse('2026-09-29 12:00:00', 'UTC'));

        $this->assertSame(1, $deleted->partitionsCount);
        $this->assertSame(10, $deleted->tracesCount);
        $this->assertSame($failure, $deleted->exception);
    }
}
