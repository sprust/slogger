<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Contracts\Actions\MakeTraceTimestampsActionInterface;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
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
