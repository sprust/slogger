<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceProfilingActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceProfilingRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Profiling\TraceProfilingTreeResource;
use App\Modules\Trace\Parameters\Profilling\TraceFindProfilingParameters;
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
                caller: ArrayValueGetter::stringNull($validated, 'caller'),
                excludedCallers: ArrayValueGetter::arrayStringNull($validated, 'excluded_callers')
            )
        );

        abort_if(!$profiling, Response::HTTP_NOT_FOUND, 'Profiling not found');

        return new TraceProfilingTreeResource($profiling);
    }
}
