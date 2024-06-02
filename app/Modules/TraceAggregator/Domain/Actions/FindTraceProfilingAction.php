<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\Profiling\ProfilingObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindProfilingParameters;
use App\Modules\TraceAggregator\Domain\Entities\Transports\TraceProfilingTransport;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTraceProfilingAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceFindProfilingParameters $parameters): ?ProfilingObject
    {
        $profiling = $this->traceRepository->findProfilingByTraceId(
            traceId: $parameters->traceId
        );

        if (empty($profiling)) {
            return null;
        }

        return TraceProfilingTransport::toObject($profiling);
    }
}
