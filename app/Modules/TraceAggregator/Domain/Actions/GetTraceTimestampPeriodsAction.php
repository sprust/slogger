<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\Timestamp\TraceTimestampPeriodObject;
use App\Modules\TraceAggregator\Domain\Services\TraceTimestampMetricsFactory;
use App\Modules\TraceAggregator\Enums\TraceTimestampEnum;
use App\Modules\TraceAggregator\Enums\TraceTimestampPeriodEnum;

readonly class GetTraceTimestampPeriodsAction
{
    public function __construct(
        private TraceTimestampMetricsFactory $traceTimestampMetricsFactory
    ) {
    }

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
                    $this->traceTimestampMetricsFactory->getTimestampsByDate($period)
                )
            ),
            TraceTimestampPeriodEnum::cases()
        );
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
