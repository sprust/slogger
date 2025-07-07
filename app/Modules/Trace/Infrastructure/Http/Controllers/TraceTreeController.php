<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreesResource;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;

readonly class TraceTreeController
{
    public function __construct(
        private FindTraceTreeActionInterface $findTraceTreeAction
    ) {
    }

    public function index(string $traceId): TraceTreesResource
    {
        $traceTreeNodeObjects = $this->findTraceTreeAction->handle(
            new TraceFindTreeParameters(
                traceId: $traceId,
                fresh: true, // TODO: to a request
                page: 1
            )
        );

        return new TraceTreesResource($traceTreeNodeObjects);
    }
}
