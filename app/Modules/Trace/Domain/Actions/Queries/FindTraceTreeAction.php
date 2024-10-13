<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TreeTooLongException;
use App\Modules\Trace\Domain\Services\TraceTreeBuilder;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObjects;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;
use App\Modules\Trace\Repositories\Dto\TraceDto;
use App\Modules\Trace\Transports\TraceDetailTransport;
use App\Modules\Trace\Transports\TraceTransport;

readonly class FindTraceTreeAction implements FindTraceTreeActionInterface
{
    private int $maxItemsCount;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
        $this->maxItemsCount = 5000;
    }

    public function handle(TraceFindTreeParameters $parameters): TraceTreeObjects
    {
        $parentTraceId = $this->traceTreeRepository->findParentTraceId(
            traceId: $parameters->traceId
        );

        if (!$parentTraceId) {
            return new TraceTreeObjects(
                tracesCount: 0,
                items: []
            );
        }

        $childrenIds = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId(
            traceId: $parentTraceId
        );

        $tracesCount = count($childrenIds) + 1;

        if ($tracesCount > $this->maxItemsCount) {
            throw new TreeTooLongException(
                limit: $this->maxItemsCount,
                current: $tracesCount
            );
        }

        $parentTrace = $this->traceRepository->findOneByTraceId(
            traceId: $parentTraceId
        );

        $children = $this->traceRepository->findByTraceIds(
            traceIds: $childrenIds
        );

        $treeNodesBuilder = new TraceTreeBuilder(
            parentTrace: TraceDetailTransport::toObject($parentTrace),
            children: collect(
                array_map(
                    fn(TraceDto $dto) => TraceTransport::toObject($dto),
                    $children
                )
            )
        );

        return new TraceTreeObjects(
            tracesCount: $tracesCount,
            items: [
                $treeNodesBuilder->build(),
            ]
        );
    }
}
