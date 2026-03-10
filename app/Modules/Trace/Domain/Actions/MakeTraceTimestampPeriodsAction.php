<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampPeriodsActionInterface;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampPeriodObject;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;

readonly class MakeTraceTimestampPeriodsAction implements MakeTraceTimestampPeriodsActionInterface
{
    public function __construct(
        private TraceTimestampMetricsFactory $traceTimestampMetricsFactory
    ) {
    }

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
