<?php

namespace App\Modules\TraceAggregator\Framework\Http\Controllers;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Domain\Exceptions\TreeTooLongException;
use App\Modules\TraceAggregator\Framework\Http\Responses\TraceTreesResponse;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
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
