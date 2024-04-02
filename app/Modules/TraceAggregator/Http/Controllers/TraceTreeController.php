<?php

namespace App\Modules\TraceAggregator\Http\Controllers;

use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Exceptions\TreeTooLongException;
use App\Modules\TraceAggregator\Http\Responses\TraceTreesResponse;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceTreeController
{
    public function __construct(
        private TraceTreeRepositoryInterface $repository,
    ) {
    }

    public function index(string $traceId): TraceTreesResponse
    {
        try {
            $traceTreeNodeObjects = $this->repository->find(
                new TraceFindTreeParameters(
                    traceId: $traceId
                )
            );
        } catch (TreeTooLongException $exception) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
        }

        return new TraceTreesResponse($traceTreeNodeObjects);
    }
}
