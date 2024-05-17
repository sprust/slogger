<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Actions\FindTraceProfilingAction;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindProfilingParameters;
use App\Modules\TraceAggregator\Framework\Http\Resources\TraceProfilingResource;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceProfilingController
{
    public function __construct(
        private FindTraceProfilingAction $findTraceProfilingAction
    ) {
    }

    public function index(string $traceId): TraceProfilingResource
    {
        $profiling = $this->findTraceProfilingAction->handle(
            new TraceFindProfilingParameters(
                traceId: $traceId
            )
        );

        abort_if(!$profiling, Response::HTTP_NOT_FOUND, 'Profiling not found');

        return new TraceProfilingResource($profiling);
    }
}
