<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Actions\FindTraceTreeAction;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Domain\Exceptions\TreeTooLongException;
use App\Modules\TraceAggregator\Framework\Http\Resources\TraceTreesResource;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceTreeController
{
    public function __construct(
        private FindTraceTreeAction $findTraceTreeAction
    ) {
    }

    public function index(string $traceId): TraceTreesResource
    {
        try {
            $traceTreeNodeObjects = $this->findTraceTreeAction->handle(
                new TraceFindTreeParameters(
                    traceId: $traceId
                )
            );
        } catch (TreeTooLongException $exception) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
        }

        return new TraceTreesResource($traceTreeNodeObjects);
    }
}
