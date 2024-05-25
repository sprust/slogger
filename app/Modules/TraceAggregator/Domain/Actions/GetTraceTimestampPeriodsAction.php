<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampPeriodObject;
use App\Modules\TraceAggregator\Enums\TraceTimestampEnum;
use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;

readonly class GetTraceTimestampPeriodsAction
{
    /**
     * @return TraceTimestampPeriodObject[]
     */
    public function handle(): array
    {
        return array_map(
            fn(TraceTimestampPeriodEnum $period) => new TraceTimestampPeriodObject(
                period: $period,
                timestamps: array_map(
                    fn(TraceTimestampEnum $timestamp) => new TraceTimestampObject(
                        value: $timestamp->value,
                        title: $this->getTimestampTitle($timestamp),
                    ),
                    $this->getTimestampsByPeriod($period)
                )
            ),
            TraceTimestampPeriodEnum::cases()
        );
    }

    /**
     * @return TraceTimestampEnum[]
     */
    private function getTimestampsByPeriod(TraceTimestampPeriodEnum $period): array
    {
        return match ($period) {
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
                TraceTimestampEnum::Min,
                TraceTimestampEnum::Min10,
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

    private function getTimestampTitle(TraceTimestampEnum $timestamp): string
    {
        return match ($timestamp) {
            TraceTimestampEnum::S5 => '5 seconds',
            TraceTimestampEnum::S10 => '10 seconds',
            TraceTimestampEnum::S30 => '30 seconds',
            TraceTimestampEnum::Min => '1 minute',
            TraceTimestampEnum::Min5 => '5 minutes',
            TraceTimestampEnum::Min10 => '10 minutes',
            TraceTimestampEnum::Min30 => '30 minutes',
            TraceTimestampEnum::H => '1 hour',
            TraceTimestampEnum::H4 => '4 hours',
            TraceTimestampEnum::H12 => '12 hours',
            TraceTimestampEnum::D => '1 day',
            TraceTimestampEnum::M => '1 month',
        };
    }
}
