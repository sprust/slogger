<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeChildrenActionInterface;
use App\Modules\Trace\Infrastructure\Http\Requests\TraceTreeChildrenRequest;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreeChildrenResource;
use App\Modules\Trace\Parameters\TraceFindTreeChildrenParameters;

readonly class TraceTreeChildrenController
{
    public function __construct(
        private FindTraceTreeChildrenActionInterface $findTraceTreeChildrenAction
    ) {
    }

    public function index(TraceTreeChildrenRequest $request): TraceTreeChildrenResource
    {
        $validated = $request->validated();

        $children = $this->findTraceTreeChildrenAction->handle(
            new TraceFindTreeChildrenParameters(
                page: ArrayValueGetter::int($validated, 'page'),
                root: ArrayValueGetter::bool($validated, 'root'),
                traceId: ArrayValueGetter::string($validated, 'trace_id'),
            )
        );

        return new TraceTreeChildrenResource($children);
    }
}
