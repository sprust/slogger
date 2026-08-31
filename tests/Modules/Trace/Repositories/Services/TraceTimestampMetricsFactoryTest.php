<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TraceTimestampMetricsFactoryTest extends TestCase
{
    /**
     * A trace joins a bucket by the truncated timestamp of its `tss` map, so the bucket is
     * [start, start + step). The end reported for it opens a search that filters `lat`
     * inclusively, which makes the last instant of the bucket the only correct answer.
     */
    #[DataProvider('bucketEndProvider')]
    public function testTheBucketEndIsOneMicrosecondBeforeTheNextBucket(
        TraceTimestampEnum $timestamp,
        string $date,
        string $expectedEnd
    ): void {
        $factory = new TraceTimestampMetricsFactory();

        $end = $factory->makeNextTimestamp(
            date: new Carbon($date, 'UTC'),
            timestamp: $timestamp
        );

        self::assertSame($expectedEnd, $end->format('Y-m-d H:i:s.u'));
    }

    /**
     * @return array<string, array{TraceTimestampEnum, string, string}>
     */
    public static function bucketEndProvider(): array
    {
        return [
            // The case the graph is drawn with: everything logged in 06:41:59 belongs to
            // the bucket that starts at 06:41:55, and the search has to be told so.
            's5'    => [TraceTimestampEnum::S5, '2026-08-31 06:41:55', '2026-08-31 06:41:59.999999'],
            's10'   => [TraceTimestampEnum::S10, '2026-08-31 06:41:50', '2026-08-31 06:41:59.999999'],
            's30'   => [TraceTimestampEnum::S30, '2026-08-31 06:41:30', '2026-08-31 06:41:59.999999'],
            'min'   => [TraceTimestampEnum::Min, '2026-08-31 06:41:00', '2026-08-31 06:41:59.999999'],
            'min5'  => [TraceTimestampEnum::Min5, '2026-08-31 06:40:00', '2026-08-31 06:44:59.999999'],
            'min10' => [TraceTimestampEnum::Min10, '2026-08-31 06:40:00', '2026-08-31 06:49:59.999999'],
            'min30' => [TraceTimestampEnum::Min30, '2026-08-31 06:30:00', '2026-08-31 06:59:59.999999'],
            'h'     => [TraceTimestampEnum::H, '2026-08-31 06:00:00', '2026-08-31 06:59:59.999999'],
            'h4'    => [TraceTimestampEnum::H4, '2026-08-31 04:00:00', '2026-08-31 07:59:59.999999'],
            'h12'   => [TraceTimestampEnum::H12, '2026-08-31 00:00:00', '2026-08-31 11:59:59.999999'],
            'd'     => [TraceTimestampEnum::D, '2026-08-31 00:00:00', '2026-08-31 23:59:59.999999'],
            // A month is the one step whose length varies, so it is taken from the calendar
            // rather than from a fixed number of days.
            'm'     => [TraceTimestampEnum::M, '2026-02-01 00:00:00', '2026-02-28 23:59:59.999999'],
        ];
    }

    /**
     * The end is asked for with whatever date the caller has, not only with an exact bucket
     * start, so it has to truncate first and answer for the bucket that date falls into.
     */
    #[DataProvider('bucketEndProvider')]
    public function testADateInsideTheBucketGetsTheSameEnd(
        TraceTimestampEnum $timestamp,
        string $date,
        string $expectedEnd
    ): void {
        $factory = new TraceTimestampMetricsFactory();

        $end = $factory->makeNextTimestamp(
            date: new Carbon($date, 'UTC')->addMicroseconds(1),
            timestamp: $timestamp
        );

        self::assertSame($expectedEnd, $end->format('Y-m-d H:i:s.u'));
    }

    /**
     * Whatever the step, the end has to meet the start of the next bucket exactly - no gap
     * a trace could fall into, no overlap that would count one twice.
     */
    #[DataProvider('bucketEndProvider')]
    public function testTheEndMeetsTheNextBucketStart(
        TraceTimestampEnum $timestamp,
        string $date,
        string $expectedEnd
    ): void {
        $factory = new TraceTimestampMetricsFactory();

        $end = $factory->makeNextTimestamp(
            date: new Carbon($date, 'UTC'),
            timestamp: $timestamp
        );

        $nextBucketStart = $factory->prepareDateByTimestamp(
            date: $end->clone()->addMicrosecond(),
            timestamp: $timestamp
        );

        self::assertSame(
            $end->clone()->addMicrosecond()->format('Y-m-d H:i:s.u'),
            $nextBucketStart->format('Y-m-d H:i:s.u')
        );
    }

    /**
     * Carbon is mutable, and the caller passes the same object it keeps as the bucket's
     * start, so asking for the end must not move it.
     */
    public function testAskingForTheEndLeavesTheDateAlone(): void
    {
        $factory = new TraceTimestampMetricsFactory();

        $date = new Carbon('2026-08-31 06:41:57.123456', 'UTC');

        $factory->makeNextTimestamp(date: $date, timestamp: TraceTimestampEnum::S5);

        self::assertSame('2026-08-31 06:41:57.123456', $date->format('Y-m-d H:i:s.u'));
    }

    /**
     * The start of the bucket is what the graph labels and what the search takes as its
     * lower bound; it has to stay a whole second whatever the date carries.
     */
    public function testTheBucketStartIsTruncatedToTheStep(): void
    {
        $factory = new TraceTimestampMetricsFactory();

        $start = $factory->prepareDateByTimestamp(
            date: new Carbon('2026-08-31 06:41:59.999999', 'UTC'),
            timestamp: TraceTimestampEnum::S5
        );

        self::assertSame('2026-08-31 06:41:55.000000', $start->format('Y-m-d H:i:s.u'));
    }
}
