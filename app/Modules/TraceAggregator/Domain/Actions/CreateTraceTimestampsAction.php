<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampMetricObject;
use App\Modules\TraceAggregator\Domain\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;

readonly class CreateTraceTimestampsAction
{
    public function __construct(private TraceTimestampMetricsFactory $traceTimestampMetricsFactory)
    {
    }

    /**
     * @return TraceTimestampMetricObject[]
     */
    public function handle(Carbon $date): array
    {
        return $this->traceTimestampMetricsFactory->createMetricsByDate(
            date: $date->clone()
        );
    }
}
