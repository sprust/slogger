<?php

namespace App\Modules\Trace\Framework\Http\Controllers;

use App\Modules\Trace\Domain\Actions\Interfaces\Queries\FindTraceProfilingActionInterface;
use App\Modules\Trace\Domain\Entities\Parameters\Profilling\TraceFindProfilingParameters;
use App\Modules\Trace\Framework\Http\Requests\TraceProfilingRequest;
use App\Modules\Trace\Framework\Http\Resources\Profiling\TraceProfilingTreeResource;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceProfilingController
{
    public function __construct(
        private FindTraceProfilingActionInterface $findTraceProfilingAction
    ) {
    }

    public function index(string $traceId, TraceProfilingRequest $request): TraceProfilingTreeResource
    {
        $validated = $request->validated();

        $profiling = $this->findTraceProfilingAction->handle(
            new TraceFindProfilingParameters(
                traceId: $traceId,
                caller: $validated['caller'] ?? null
            )
        );

        abort_if(!$profiling, Response::HTTP_NOT_FOUND, 'Profiling not found');

        return new TraceProfilingTreeResource($profiling);
    }
}
