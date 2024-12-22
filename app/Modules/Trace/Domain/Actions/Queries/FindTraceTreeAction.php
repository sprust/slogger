<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceServicesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TreeTooLongException;
use App\Modules\Trace\Domain\Services\TraceTreeBuilder;
use App\Modules\Trace\Entities\Trace\TraceObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeObjects;
use App\Modules\Trace\Parameters\TraceFindTreeParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use Illuminate\Support\Arr;

readonly class FindTraceTreeAction implements FindTraceTreeActionInterface
{
    private int $maxItemsCount;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private FindTraceServicesActionInterface $findTraceServicesAction
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

        $foundTraces = $this->traceRepository->findByTraceIds(
            traceIds: [
                $parentTraceId,
                ...$childrenIds,
            ]
        );

        /** @var int[] $serviceIds */
        $serviceIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn(TraceDto $trace) => $trace->serviceId,
                        $foundTraces
                    )
                )
            )
        );

        $services = $this->findTraceServicesAction->handle(
            serviceIds: $serviceIds
        );

        $traces = array_map(
            fn(TraceDto $trace) => new TraceObject(
                id: $trace->id,
                service: $trace->serviceId
                    ? $services->getById($trace->serviceId)
                    : null,
                traceId: $trace->traceId,
                parentTraceId: $trace->parentTraceId,
                type: $trace->type,
                status: $trace->status,
                tags: $trace->tags,
                duration: $trace->duration,
                memory: $trace->memory,
                cpu: $trace->cpu,
                loggedAt: $trace->loggedAt,
                createdAt: $trace->createdAt,
                updatedAt: $trace->updatedAt,
            ),
            $foundTraces
        );

        $treeNodesBuilder = new TraceTreeBuilder(
            parentTrace: Arr::first($traces, fn(TraceObject $trace) => $trace->traceId === $parentTraceId),
            children: array_filter(
                $traces,
                static fn(TraceObject $trace) => $trace->traceId !== $parentTraceId
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
