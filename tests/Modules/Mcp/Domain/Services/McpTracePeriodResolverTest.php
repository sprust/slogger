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
    public function testTakesThePeriodExactly(): void
    {
        $this->assertPeriod('2026-09-28 10:20:00', '2026-09-28 12:05:00', '2026-09-28 10:20:00', '2026-09-28 12:05:00');
    }

    public function testHourBoundaryIsKept(): void
    {
        $this->assertPeriod('2026-09-28 10:00:00', '2026-09-28 12:00:00', '2026-09-28 10:00:00', '2026-09-28 12:00:00');
    }

    public function testWholeRetentionPasses(): void
    {
        $this->assertPeriod('2026-09-25 10:30:00', '2026-09-28 10:30:00', '2026-09-25 10:30:00', '2026-09-28 10:30:00');
    }

    public function testLongerThanRetentionIsTooWide(): void
    {
        try {
            $this->resolve('2026-09-25 02:00:00', '2026-09-28 10:00:00');
        } catch (McpTracePeriodTooWideException $exception) {
            $this->assertSame(72, $exception->maxHours);

            return;
        }

        $this->fail('The period is not rejected');
    }

    public function testASecondOverRetentionIsTooWide(): void
    {
        $this->expectException(McpTracePeriodTooWideException::class);

        $this->resolve('2026-09-25 10:30:00', '2026-09-28 10:30:01');
    }

    public function testFromAfterToIsInvalid(): void
    {
        $this->expectException(McpTraceInvalidPeriodException::class);

        $this->resolve('2026-09-28 12:00:00', '2026-09-28 11:00:00');
    }

    public function testEmptyPeriodIsInvalid(): void
    {
        $this->expectException(McpTraceInvalidPeriodException::class);

        $this->resolve('2026-09-28 10:00:00', '2026-09-28 10:00:00');
    }

    public function testOtherZoneIsCountedInUtc(): void
    {
        $period = new McpTracePeriodResolver(maxHours: 72)->resolve(
            Carbon::parse('2026-09-28 13:20:00', 'Europe/Moscow'),
            Carbon::parse('2026-09-28 15:05:00', 'Europe/Moscow')
        );

        $this->assertSame('2026-09-28T10:20:00+00:00', $period->from->toIso8601String());
        $this->assertSame('2026-09-28T12:05:00+00:00', $period->to->toIso8601String());
    }

    public function testGivenBoundsAreNotChanged(): void
    {
        $from = Carbon::parse('2026-09-28 13:20:00', 'Europe/Moscow');
        $to   = Carbon::parse('2026-09-28 15:05:00', 'Europe/Moscow');

        new McpTracePeriodResolver(maxHours: 72)->resolve($from, $to);

        $this->assertSame('Europe/Moscow', $from->getTimezone()->getName());
        $this->assertSame('2026-09-28 15:05:00', $to->toDateTimeString());
    }

    private function assertPeriod(string $expectedFrom, string $expectedTo, string $from, string $to): void
    {
        $period = $this->resolve($from, $to);

        $this->assertSame($expectedFrom, $period->from->toDateTimeString());
        $this->assertSame($expectedTo, $period->to->toDateTimeString());
    }

    private function resolve(string $from, string $to): McpTracePeriodObject
    {
        return new McpTracePeriodResolver(maxHours: 72)->resolve(Carbon::parse($from, 'UTC'), Carbon::parse($to, 'UTC'));
    }
}
