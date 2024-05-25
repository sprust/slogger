<?php

namespace App\Modules\TraceAggregator\Domain\Services;

use App\Modules\Common\Enums\TraceTimestampMetricEnum;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampMetricsObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObject;
use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TraceTimestampMetricsFactory
{
    public function createMetricsByDate(Carbon $date): TraceTimestampMetricsObject
    {
        $date = $date->clone()->setMicroseconds(0);

        return new TraceTimestampMetricsObject(
            m: $date->clone()->startOfMonth(),
            d: $date->clone()->startOfDay(),
            h12: $this->sliceHours($date->clone(), 12),
            h4: $this->sliceHours($date->clone(), 4),
            h: $this->sliceHours($date->clone(), 1),
            min30: $this->sliceMinutes($date->clone(), 30),
            min10: $this->sliceMinutes($date->clone(), 10),
            min5: $this->sliceMinutes($date->clone(), 5),
            min: $this->sliceMinutes($date->clone(), 1),
            s30: $this->sliceSeconds($date->clone(), 30),
            s10: $this->sliceSeconds($date->clone(), 10),
            s5: $this->sliceSeconds($date->clone(), 5)
        );
    }

    public function calcLoggedAtFrom(TraceTimestampPeriodEnum $timestampPeriod, Carbon $date): Carbon
    {
        return match ($timestampPeriod) {
            TraceTimestampPeriodEnum::Minute5 => $date->clone()->subMinutes(5),
            TraceTimestampPeriodEnum::Minute30 => $date->clone()->subMinutes(30),
            TraceTimestampPeriodEnum::Hour => $date->clone()->subHour(),
            TraceTimestampPeriodEnum::Hour4 => $date->clone()->subHours(4),
            TraceTimestampPeriodEnum::Hour12 => $date->clone()->subHours(12),
            TraceTimestampPeriodEnum::Day => $date->clone()->subDay(),
            TraceTimestampPeriodEnum::Day3 => $date->clone()->subDays(3),
            TraceTimestampPeriodEnum::Day7 => $date->clone()->subDays(7),
            TraceTimestampPeriodEnum::Day15 => $date->clone()->subDays(15),
            TraceTimestampPeriodEnum::Month => $date->clone()->subMonth(),
            TraceTimestampPeriodEnum::Month3 => $date->clone()->subMonths(3),
            TraceTimestampPeriodEnum::Month6 => $date->clone()->subMonths(6),
            TraceTimestampPeriodEnum::Year => $date->clone()->subYear(),
        };
    }

    public function calcTimestampMetric(TraceTimestampPeriodEnum $timestampPeriod): TraceTimestampMetricEnum
    {
        return match ($timestampPeriod) {
            TraceTimestampPeriodEnum::Minute5 => TraceTimestampMetricEnum::S5,
            TraceTimestampPeriodEnum::Minute30 => TraceTimestampMetricEnum::S30,
            TraceTimestampPeriodEnum::Hour => TraceTimestampMetricEnum::Min,
            TraceTimestampPeriodEnum::Hour4 => TraceTimestampMetricEnum::Min5,
            TraceTimestampPeriodEnum::Hour12 => TraceTimestampMetricEnum::Min10,
            TraceTimestampPeriodEnum::Day => TraceTimestampMetricEnum::Min30,
            TraceTimestampPeriodEnum::Day3 => TraceTimestampMetricEnum::H,
            TraceTimestampPeriodEnum::Day7 => TraceTimestampMetricEnum::H4,
            TraceTimestampPeriodEnum::Day15, TraceTimestampPeriodEnum::Month  => TraceTimestampMetricEnum::H12,
            TraceTimestampPeriodEnum::Month3 => TraceTimestampMetricEnum::D,
            TraceTimestampPeriodEnum::Month6, TraceTimestampPeriodEnum::Year => TraceTimestampMetricEnum::M,
        };
    }

    /**
     * @param TraceTimestampsObject[] $existsTimestamps
     *
     * @return TraceTimestampsObject[]
     */
    public function makeTimeLine(
        Carbon $dateFrom,
        Carbon $dateTo,
        TraceTimestampMetricEnum $timestampMetric,
        array $existsTimestamps
    ): array {
        /** @var Collection<string, TraceTimestampsObject> $timestampsKeyByTimestamp */
        $timestampsKeyByTimestamp = collect($existsTimestamps)->keyBy(
            fn(TraceTimestampsObject $object) => $object->timestamp->toDateTimeString()
        );

        /** @var TraceTimestampsObject[] $timestamps */
        $timestamps = [];

        $iterator = $dateTo->clone();

        while (true) {
            $iterator = match ($timestampMetric) {
                TraceTimestampMetricEnum::M => $iterator->clone()->startOfMonth(),
                TraceTimestampMetricEnum::D => $iterator->clone()->startOfDay(),
                TraceTimestampMetricEnum::H12 => $this->sliceHours($iterator->clone(), 12),
                TraceTimestampMetricEnum::H4 => $this->sliceHours($iterator->clone(), 4),
                TraceTimestampMetricEnum::H => $this->sliceHours($iterator->clone(), 1),
                TraceTimestampMetricEnum::Min30 => $this->sliceMinutes($iterator->clone(), 30),
                TraceTimestampMetricEnum::Min10 => $this->sliceMinutes($iterator->clone(), 10),
                TraceTimestampMetricEnum::Min5 => $this->sliceMinutes($iterator->clone(), 5),
                TraceTimestampMetricEnum::Min => $this->sliceMinutes($iterator->clone(), 1),
                TraceTimestampMetricEnum::S30 => $this->sliceSeconds($iterator->clone(), 30),
                TraceTimestampMetricEnum::S10 => $this->sliceSeconds($iterator->clone(), 10),
                TraceTimestampMetricEnum::S5 => $this->sliceSeconds($iterator->clone(), 5),
            };

            $timestamps[] = $timestampsKeyByTimestamp[$iterator->toDateTimeString()]
                ?? new TraceTimestampsObject(
                    timestamp: $iterator->clone(),
                    count: 0
                );

            $iterator = $iterator->subSecond();

            if ($iterator->lt($dateFrom)) {
                break;
            }
        }

        return collect($timestamps)
            ->sortBy(fn(TraceTimestampsObject $object) => $object->timestamp->toDateTimeString())
            ->values()
            ->toArray();
    }

    private function sliceHours(Carbon $date, int $slice): Carbon
    {
        return $date
            ->setHours(
                $this->sliceValue($date->hour, $slice)
            )
            ->startOfHour();
    }

    private function sliceMinutes(Carbon $date, int $slice): Carbon
    {
        return $date
            ->setMinutes(
                $this->sliceValue($date->minute, $slice)
            )
            ->startOfMinute();
    }

    private function sliceSeconds(Carbon $date, int $slice): Carbon
    {
        return $date
            ->setSeconds(
                $this->sliceValue($date->second, $slice)
            )
            ->startOfSecond();
    }

    private function sliceValue(int $value, int $slice): int
    {
        return $value - ($value % $slice);
    }
}
