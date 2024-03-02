<?php

namespace App\Modules\TraceAggregator\Http\Controllers;

use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Http\Responses\TraceTreesResponse;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepository;

readonly class TraceTreeController
{
    public function __construct(
        private TraceTreeRepository $repository,
    ) {
    }

    public function tree(string $traceId): TraceTreesResponse
    {
        $traceTreeNodeObjects = $this->repository->find(
            new TraceFindTreeParameters(
                traceId: $traceId
            )
        );

        return new TraceTreesResponse($traceTreeNodeObjects);
    }
}
