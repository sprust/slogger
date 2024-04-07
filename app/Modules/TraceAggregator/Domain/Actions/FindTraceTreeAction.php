<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeObjects;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Domain\Exceptions\TreeTooLongException;
use App\Modules\TraceAggregator\Domain\Services\TraceTreeBuilder;
use App\Modules\TraceAggregator\Domain\Transports\TraceDetailTransport;
use App\Modules\TraceAggregator\Domain\Transports\TraceTransport;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceServiceDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;

readonly class FindTraceTreeAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    /**
     * @throws TreeTooLongException
     */
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

        if ($tracesCount > 3000) {
            throw new TreeTooLongException(
                limit: 3000,
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
