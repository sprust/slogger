<?php

namespace Tests\Modules\Dashboard\Domain\Actions;

use App\Modules\Dashboard\Domain\Actions\FindTraceMetricsAction;
use App\Modules\Dashboard\Repositories\TraceMetricRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FindTraceMetricsActionTest extends TestCase
{
    public function testTheWindowIsTheLastDayOfSlotsEndingWithTheCurrentOne(): void
    {
        $this->assertWindowStartsAt('2026-09-10 12:15:00', now: '2026-09-11 12:07:42');
    }

    public function testASlotBoundaryIsTheStartOfTheCurrentSlot(): void
    {
        $this->assertWindowStartsAt('2026-09-10 12:30:00', now: '2026-09-11 12:15:00');
    }

    public function testTheWindowIsCountedInUtc(): void
    {
        $this->assertWindowStartsAt(
            '2026-09-10 12:15:00',
            now: Carbon::parse('2026-09-11 15:07:42', 'Europe/Moscow')->toIso8601String()
        );
    }

    private function assertWindowStartsAt(string $expected, string $now): void
    {
        $repository = $this->createMock(TraceMetricRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(
                7,
                $this->callback(
                    static fn(Carbon $from): bool => $from->utc()->toDateTimeString() === $expected
                )
            )
            ->willReturn([]);

        new FindTraceMetricsAction($repository)->handle(7, Carbon::parse($now));
    }
}
