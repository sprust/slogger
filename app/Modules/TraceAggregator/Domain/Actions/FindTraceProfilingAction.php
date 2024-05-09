<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingItemObject;
use App\Modules\TraceAggregator\Domain\Services\TraceProfileBuilder;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;

readonly class FindTraceProfilingAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceProfileBuilder $traceProfileBuilder
    ) {
    }

    /**
     * @return ProfilingItemObject[]
     */
    public function handle(string $traceId): array
    {
        $profiling = $this->traceRepository->findProfilingByTraceId($traceId);

        if (empty($profiling)) {
            return [];
        }

        return $this->traceProfileBuilder->build($profiling);
    }
}
