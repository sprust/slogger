<?php

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDataRangeAction;
use App\Modules\Trace\Entities\Trace\TraceDataRangeObject;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FindTraceDataRangeActionTest extends TestCase
{
    public function testFirstAndLastHours(): void
    {
        $repository = $this->createMock(TraceRepository::class);

        $repository->expects($this->once())->method('findHourRange')->willReturn(
            new TraceDataRangeObject(
                firstHour: Carbon::parse('2026-09-25 10:00:00', 'UTC'),
                lastHour: Carbon::parse('2026-09-28 14:00:00', 'UTC')
            )
        );

        $range = new FindTraceDataRangeAction($repository)->handle();

        $this->assertSame('2026-09-25T10:00:00+00:00', $range->firstHour?->toIso8601String());
        $this->assertSame('2026-09-28T14:00:00+00:00', $range->lastHour?->toIso8601String());
    }

    public function testNoTraces(): void
    {
        $repository = $this->createMock(TraceRepository::class);

        $repository->method('findHourRange')->willReturn(
            new TraceDataRangeObject(firstHour: null, lastHour: null)
        );

        $range = new FindTraceDataRangeAction($repository)->handle();

        $this->assertNull($range->firstHour);
        $this->assertNull($range->lastHour);
    }
}
