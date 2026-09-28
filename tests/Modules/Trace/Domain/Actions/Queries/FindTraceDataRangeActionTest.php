<?php

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\FindTraceDataRangeAction;
use App\Modules\Trace\Repositories\Services\PeriodicTraceCollectionNameService;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use PHPUnit\Framework\TestCase;

class FindTraceDataRangeActionTest extends TestCase
{
    public function testFirstAndLastHours(): void
    {
        $service = $this->createMock(PeriodicTraceService::class);

        $service->method('detectCollectionNames')->willReturn([
            'traces_2026_09_25_10_11',
            'traces_2026_09_26_00_01',
            'traces_2026_09_28_14_15',
        ]);

        $range = new FindTraceDataRangeAction($service, new PeriodicTraceCollectionNameService())->handle();

        $this->assertSame('2026-09-25T10:00:00+00:00', $range->firstHour?->toIso8601String());
        $this->assertSame('2026-09-28T14:00:00+00:00', $range->lastHour?->toIso8601String());
    }

    public function testNoCollections(): void
    {
        $service = $this->createMock(PeriodicTraceService::class);

        $service->method('detectCollectionNames')->willReturn([]);

        $range = new FindTraceDataRangeAction($service, new PeriodicTraceCollectionNameService())->handle();

        $this->assertNull($range->firstHour);
        $this->assertNull($range->lastHour);
    }
}
