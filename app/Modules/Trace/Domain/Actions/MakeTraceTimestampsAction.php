<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Actions\Interfaces\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
use Illuminate\Support\Carbon;

readonly class MakeTraceTimestampsAction implements MakeTraceTimestampsActionInterface
{
    public function __construct(private TraceTimestampMetricsFactory $traceTimestampMetricsFactory)
    {
    }

    public function handle(Carbon $date): array
    {
        return $this->traceTimestampMetricsFactory->makeMetricsByDate(
            date: $date->clone()
        );
    }
}
