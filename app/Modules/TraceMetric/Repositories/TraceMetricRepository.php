<?php

namespace App\Modules\TraceMetric\Repositories;

use App\Models\Traces\TraceMetric;
use Illuminate\Support\Carbon;

class TraceMetricRepository implements TraceMetricRepositoryInterface
{
    public function create(
        int $serviceId,
        string $type,
        string $status,
        Carbon $timestamp,
        int $count
    ): void {
        $metric = new TraceMetric();

        $metric->serviceId = $serviceId;
        $metric->type      = $type;
        $metric->status    = $status;
        $metric->timestamp = $timestamp;
        $metric->count = $count;

        $metric->save();
    }
}
