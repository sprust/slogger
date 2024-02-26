<?php

namespace App\Modules\TracesAggregator\Http\Controllers;

use App\Modules\TracesAggregator\Dto\Parameters\TraceMapFindParameters;
use App\Modules\TracesAggregator\Http\Responses\TraceAggregatorTreeNodesResponse;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepository;

readonly class TraceAggregatorTreeController
{
    public function __construct(
        private TraceTreeRepository $traceTreeRepository,
    ) {
    }

    public function tree(string $traceId): TraceAggregatorTreeNodesResponse
    {
        $traceTreeNodeObjects = $this->traceTreeRepository->findTraces(
            new TraceMapFindParameters(
                traceId: $traceId
            )
        );

        return new TraceAggregatorTreeNodesResponse($traceTreeNodeObjects);
    }
}