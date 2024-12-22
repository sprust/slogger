<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceProfilingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Services\TraceProfilingTreeBuilder;
use App\Modules\Trace\Entities\Trace\Profiling\ProfilingTreeObject;
use App\Modules\Trace\Parameters\Profilling\TraceFindProfilingParameters;

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
