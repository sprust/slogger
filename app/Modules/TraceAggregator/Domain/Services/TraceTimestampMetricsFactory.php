<?php

namespace App\Modules\TraceAggregator\Domain\Services;

use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampMetricObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampsObject;
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
                    timestampTo: $this->makeNextTimestamp(
                        date: $iterator->clone(),
                        timestamp: $timestamp
                    ),
                    count: 0,
                    durationPercent: 0
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

    public function prepareDateByTimestamp(Carbon $date, TraceTimestampEnum $timestamp): Carbon
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

    public function makeNextTimestamp(Carbon $date, TraceTimestampEnum $timestamp): Carbon
    {
        return match ($timestamp) {
            TraceTimestampEnum::M => $date->clone()->endOfMonth(),
            TraceTimestampEnum::D => $date->clone()->endOfDay(),
            TraceTimestampEnum::H12 => $this->sliceHours($date->clone(), 12, true),
            TraceTimestampEnum::H4 => $this->sliceHours($date->clone(), 4, true),
            TraceTimestampEnum::H => $date->clone()->endOfHour(),
            TraceTimestampEnum::Min30 => $this->sliceMinutes($date->clone(), 30, true),
            TraceTimestampEnum::Min10 => $this->sliceMinutes($date->clone(), 10, true),
            TraceTimestampEnum::Min5 => $this->sliceMinutes($date->clone(), 5, true),
            TraceTimestampEnum::Min => $date->clone()->endOfMinute(),
            TraceTimestampEnum::S30 => $this->sliceSeconds($date->clone(), 30, true),
            TraceTimestampEnum::S10 => $this->sliceSeconds($date->clone(), 10, true),
            TraceTimestampEnum::S5 => $this->sliceSeconds($date->clone(), 5, true),
        };
    }

    /**
     * @return TraceTimestampEnum[]
     */
    public function getTimestampsByDate(TraceTimestampPeriodEnum $date): array
    {
        return match ($date) {
            TraceTimestampPeriodEnum::Minute5 => [
                TraceTimestampEnum::S5,
                TraceTimestampEnum::S10,
                TraceTimestampEnum::S30,
                TraceTimestampEnum::Min,
            ],
            TraceTimestampPeriodEnum::Minute30 => [
                TraceTimestampEnum::S30,
                TraceTimestampEnum::Min,
                TraceTimestampEnum::Min10,
            ],
            TraceTimestampPeriodEnum::Hour => [
                TraceTimestampEnum::Min,
                TraceTimestampEnum::Min10,
                TraceTimestampEnum::Min30,
            ],
            TraceTimestampPeriodEnum::Hour4 => [
                TraceTimestampEnum::Min30,
                TraceTimestampEnum::H,
            ],
            TraceTimestampPeriodEnum::Hour12 => [
                TraceTimestampEnum::Min30,
                TraceTimestampEnum::H,
                TraceTimestampEnum::H4,
            ],
            TraceTimestampPeriodEnum::Day => [
                TraceTimestampEnum::Min30,
                TraceTimestampEnum::H,
                TraceTimestampEnum::H4,
                TraceTimestampEnum::H12,
            ],
            TraceTimestampPeriodEnum::Day3 => [
                TraceTimestampEnum::H,
                TraceTimestampEnum::H4,
                TraceTimestampEnum::H12,
                TraceTimestampEnum::D,
            ],
            TraceTimestampPeriodEnum::Day7 => [
                TraceTimestampEnum::H4,
                TraceTimestampEnum::H12,
                TraceTimestampEnum::D,
            ],
            TraceTimestampPeriodEnum::Day15, TraceTimestampPeriodEnum::Month => [
                TraceTimestampEnum::H12,
                TraceTimestampEnum::D,
            ],
            TraceTimestampPeriodEnum::Month3 => [
                TraceTimestampEnum::D,
            ],
            TraceTimestampPeriodEnum::Month6 => [
                TraceTimestampEnum::D,
                TraceTimestampEnum::M,
            ],
            TraceTimestampPeriodEnum::Year => [
                TraceTimestampEnum::M,
            ],
        };
    }

    private function sliceHours(Carbon $date, int $slice, bool $next = false): Carbon
    {
        return $date
            ->setHours(
                $this->sliceValue($date->hour, $slice, $next)
            )
            ->startOfHour();
    }

    private function sliceMinutes(Carbon $date, int $slice, bool $next = false): Carbon
    {
        return $date
            ->setMinutes(
                $this->sliceValue($date->minute, $slice, $next)
            )
            ->startOfMinute();
    }

    private function sliceSeconds(Carbon $date, int $slice, bool $next = false): Carbon
    {
        return $date
            ->setSeconds(
                $this->sliceValue($date->second, $slice, $next)
            )
            ->startOfSecond();
    }

    private function sliceValue(int $value, int $slice, bool $next = false): int
    {
        $result = $value - ($value % $slice);

        return $next ? ($result + $slice - 1) : $result;
    }
}
