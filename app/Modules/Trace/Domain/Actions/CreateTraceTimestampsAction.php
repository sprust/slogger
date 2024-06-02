<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampMetricObject;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
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
