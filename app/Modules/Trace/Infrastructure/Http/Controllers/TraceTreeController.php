<?php

namespace App\Modules\Trace\Infrastructure\Http\Controllers;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Domain\Exceptions\TreeTooLongException;
use App\Modules\Trace\Infrastructure\Http\Resources\Tree\TraceTreesResource;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;
use Symfony\Component\HttpFoundation\Response;

readonly class TraceTreeController
{
    public function __construct(
        private FindTraceTreeActionInterface $findTraceTreeAction
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
