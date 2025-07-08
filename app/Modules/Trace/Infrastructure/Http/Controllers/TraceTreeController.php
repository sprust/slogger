<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeIndexRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreesResource;

readonly class TraceTreeController
{
    public function __construct(
        private FindTraceTreeActionInterface $findTraceTreeAction
    ) {
    }

    public function index(TraceTreeIndexRequest $request): TraceTreesResource
    {
        $validated = $request->validated();

        $traceTreeObjects = $this->findTraceTreeAction->handle(
            traceId: ArrayValueGetter::string($validated, 'trace_id'),
            fresh: ArrayValueGetter::bool($validated, 'fresh'),
            page: ArrayValueGetter::int($validated, 'page'),
        );

        return new TraceTreesResource($traceTreeObjects);
    }
}
