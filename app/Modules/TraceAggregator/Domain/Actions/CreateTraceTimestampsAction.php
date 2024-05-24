<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampMetricsObject;
use App\Modules\TraceAggregator\Domain\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;

readonly class CreateTraceTimestampsAction
{
    public function __construct(private TraceTimestampMetricsFactory $traceTimestampMetricsFactory)
    {
    }

    public function handle(Carbon $date): TraceTimestampMetricsObject
    {
        return $this->traceTimestampMetricsFactory->createMetricsByDate(
            date: $date->clone()
        );
    }
}
