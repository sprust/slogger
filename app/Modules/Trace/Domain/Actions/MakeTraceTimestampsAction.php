<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampMetricObject;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;

readonly class MakeTraceTimestampsAction
{
    public function __construct(private TraceTimestampMetricsFactory $traceTimestampMetricsFactory)
    {
    }

    /**
     * @return TraceTimestampMetricObject[]
     */
    public function handle(Carbon $date): array
    {
        return $this->traceTimestampMetricsFactory->makeMetricsByDate(
            date: $date->clone()
        );
    }
}
