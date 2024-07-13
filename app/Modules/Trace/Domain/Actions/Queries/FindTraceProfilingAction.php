<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceProfilingActionInterface;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeObject;
use App\Modules\Trace\Domain\Entities\Parameters\Profilling\TraceFindProfilingParameters;
use App\Modules\Trace\Domain\Services\TraceProfilingTreeBuilder;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTraceProfilingAction implements FindTraceProfilingActionInterface
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceFindProfilingParameters $parameters): ?ProfilingTreeObject
    {
        $profiling = $this->traceRepository->findProfilingByTraceId(
            traceId: $parameters->traceId
        );

        if (empty($profiling)) {
            return null;
        }

        $builder = new TraceProfilingTreeBuilder(
            profiling: $profiling,
            caller: $parameters->caller,
            excludedCallers: $parameters->excludedCallers
        );

        return $builder->build();
    }
}
