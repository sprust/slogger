<?php

namespace Tests\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Domain\Exceptions\McpTraceInvalidPeriodException;
use App\Modules\Mcp\Domain\Exceptions\McpTracePeriodTooWideException;
use App\Modules\Mcp\Domain\Services\McpTracePeriodResolver;
use App\Modules\Mcp\Entities\McpTracePeriodObject;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class McpTracePeriodResolverTest extends TestCase
{
    public function testAlignsOutwardToHours(): void
    {
        $this->assertPeriod('2026-09-28 10:00:00', '2026-09-28 13:00:00', '2026-09-28 10:20:00', '2026-09-28 12:05:00');
    }

    public function testHourBoundaryIsKept(): void
    {
        $this->assertPeriod('2026-09-28 10:00:00', '2026-09-28 12:00:00', '2026-09-28 10:00:00', '2026-09-28 12:00:00');
    }

    public function testEqualBoundsTakeTheirHour(): void
    {
        $this->assertPeriod('2026-09-28 10:00:00', '2026-09-28 11:00:00', '2026-09-28 10:00:00', '2026-09-28 10:00:00');
    }

    public function testExactlyADayPasses(): void
    {
        $this->assertPeriod('2026-09-27 10:00:00', '2026-09-28 10:00:00', '2026-09-27 10:00:00', '2026-09-28 10:00:00');
    }

    public function testAlignedDayAndAnHourIsTooWide(): void
    {
        $this->expectException(McpTracePeriodTooWideException::class);

        $this->resolve('2026-09-27 10:30:00', '2026-09-28 10:30:00');
    }

    public function testFromAfterToIsInvalid(): void
    {
        $this->expectException(McpTraceInvalidPeriodException::class);

        $this->resolve('2026-09-28 12:00:00', '2026-09-28 11:00:00');
    }

    public function testOtherZoneIsCountedInUtc(): void
    {
        $period = new McpTracePeriodResolver()->resolve(
            Carbon::parse('2026-09-28 13:20:00', 'Europe/Moscow'),
            Carbon::parse('2026-09-28 15:05:00', 'Europe/Moscow')
        );

        $this->assertSame('2026-09-28T10:00:00+00:00', $period->from->toIso8601String());
        $this->assertSame('2026-09-28T13:00:00+00:00', $period->to->toIso8601String());
    }

    private function assertPeriod(string $expectedFrom, string $expectedTo, string $from, string $to): void
    {
        $period = $this->resolve($from, $to);

        $this->assertSame($expectedFrom, $period->from->toDateTimeString());
        $this->assertSame($expectedTo, $period->to->toDateTimeString());
    }

    private function resolve(string $from, string $to): McpTracePeriodObject
    {
        return new McpTracePeriodResolver()->resolve(Carbon::parse($from, 'UTC'), Carbon::parse($to, 'UTC'));
    }
}
