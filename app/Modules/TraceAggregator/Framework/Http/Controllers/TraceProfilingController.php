<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Actions\FindTraceProfilingAction;
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
    public function index(string $traceId): AnonymousResourceCollection
    {
        $profiling = $this->findTraceProfilingAction->handle(
            traceId: $traceId
        );

        return TraceProfilingResource::collection($profiling);
    }
}
