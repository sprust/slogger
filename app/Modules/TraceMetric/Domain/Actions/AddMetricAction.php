<?php

namespace App\Modules\TraceMetric\Domain\Actions;

use App\Modules\TraceMetric\Domain\Entities\Parameters\AddMetricParameters;
use App\Modules\TraceMetric\Repositories\TraceMetricRepositoryInterface;

readonly class AddMetricAction
{
    public function __construct(private TraceMetricRepositoryInterface $traceMetricRepository)
    {
    }

    public function handle(AddMetricParameters $parameters): void
    {
        $this->traceMetricRepository->create(
            serviceId: $parameters->serviceId,
            type: $parameters->type,
            status: $parameters->status,
            timestamp: now(),
            count: 1
        );
    }
}
