<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Actions\FindTraceProfilingAction;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindProfilingParameters;
use App\Modules\TraceAggregator\Framework\Http\Requests\TraceProfilingIndexRequest;
use App\Modules\TraceAggregator\Framework\Http\Resources\TraceProfilingResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class TraceProfilingController
{
    public function __construct(
        private FindTraceProfilingAction $findTraceProfilingAction
    ) {
    }

    #[OaListItemTypeAttribute(TraceProfilingResource::class, isRecursive: true)]
    public function index(TraceProfilingIndexRequest $request, string $traceId): AnonymousResourceCollection
    {
        $profiling = $this->findTraceProfilingAction->handle(
            new TraceFindProfilingParameters(
                traceId: $traceId,
                call: $request['call'] ?? null
            )
        );

        return TraceProfilingResource::collection($profiling);
    }
}
