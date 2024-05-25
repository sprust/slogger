<?php

namespace App\Modules\TraceAggregator\Domain\Services;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampMetricObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObject;
use App\Modules\TraceAggregator\Enums\TraceTimestampEnum;
use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TraceTimestampMetricsFactory
{
    /**
     * @return TraceTimestampMetricObject[]
     */
    public function createMetricsByDate(Carbon $date): array
    {
        $date = $date->clone()->setMicroseconds(0);

        return array_map(
            fn(TraceTimestampEnum $timestamp) => new TraceTimestampMetricObject(
                key: $timestamp->value,
                value: $this->prepareDateByTimestamp(
                    date: $date,
                    timestamp: $timestamp
                )
            ),
            TraceTimestampEnum::cases()
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

    /**
     * @param TraceTimestampsObject[] $existsTimestamps
     *
     * @return TraceTimestampsObject[]
     */
    public function makeTimeLine(
        Carbon $dateFrom,
        Carbon $dateTo,
        TraceTimestampEnum $timestamp,
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
            $iterator = $this->prepareDateByTimestamp(
                date: $iterator,
                timestamp: $timestamp
            );

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

    private function prepareDateByTimestamp(Carbon $date, TraceTimestampEnum $timestamp): Carbon
    {
        return match ($timestamp) {
            TraceTimestampEnum::M => $date->clone()->startOfMonth(),
            TraceTimestampEnum::D => $date->clone()->startOfDay(),
            TraceTimestampEnum::H12 => $this->sliceHours($date->clone(), 12),
            TraceTimestampEnum::H4 => $this->sliceHours($date->clone(), 4),
            TraceTimestampEnum::H => $this->sliceHours($date->clone(), 1),
            TraceTimestampEnum::Min30 => $this->sliceMinutes($date->clone(), 30),
            TraceTimestampEnum::Min10 => $this->sliceMinutes($date->clone(), 10),
            TraceTimestampEnum::Min5 => $this->sliceMinutes($date->clone(), 5),
            TraceTimestampEnum::Min => $this->sliceMinutes($date->clone(), 1),
            TraceTimestampEnum::S30 => $this->sliceSeconds($date->clone(), 30),
            TraceTimestampEnum::S10 => $this->sliceSeconds($date->clone(), 10),
            TraceTimestampEnum::S5 => $this->sliceSeconds($date->clone(), 5),
        };
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
