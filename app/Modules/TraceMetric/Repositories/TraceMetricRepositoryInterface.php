<?php

namespace App\Modules\TraceMetric\Repositories;

use Illuminate\Support\Carbon;

interface TraceMetricRepositoryInterface
{
    public function create(
        int $serviceId,
        string $type,
        string $status,
        Carbon $timestamp,
        int $count
    ): void;
}
