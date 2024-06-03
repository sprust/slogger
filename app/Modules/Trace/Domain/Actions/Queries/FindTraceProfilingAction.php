<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingObject;
use App\Modules\Trace\Domain\Entities\Parameters\Profilling\TraceFindProfilingParameters;
use App\Modules\Trace\Domain\Entities\Transports\TraceProfilingTransport;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

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
