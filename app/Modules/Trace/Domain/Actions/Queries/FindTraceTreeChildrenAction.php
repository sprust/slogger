<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceServicesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTreeChildrenActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenObjects;
use App\Modules\Trace\Parameters\TraceFindTreeChildrenParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;

readonly class FindTraceTreeChildrenAction implements FindTraceTreeChildrenActionInterface
{
    private int $perPage;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private FindTraceServicesActionInterface $findTraceServicesAction
    ) {
        $this->perPage = 50;
    }

    public function handle(TraceFindTreeChildrenParameters $parameters): TraceTreeChildrenObjects
    {
        if ($parameters->root) {
            $parentTraceId = $this->traceTreeRepository->findParentTraceId(
                traceId: $parameters->traceId
            );
        } else {
            $parentTraceId = $parameters->traceId;
        }

        if (!$parentTraceId) {
            return new TraceTreeChildrenObjects(
                items: [],
                hasMore: false
            );
        }

        $foundTraces = $this->traceRepository->find(
            page: $parameters->page,
            perPage: $this->perPage + 1,
            parentTraceId: $parentTraceId
        );

        $hasMore = count($foundTraces) > $this->perPage;

        $foundTraces = array_slice($foundTraces, 0, $this->perPage);

        /** @var int[] $serviceIds */
        $serviceIds = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn(TraceDto $trace) => $trace->serviceId,
                        $foundTraces
                    )
                )
            )
        );

        $services = $this->findTraceServicesAction->handle(
            serviceIds: $serviceIds
        );

        $traces = array_map(
            static fn(TraceDto $trace) => new TraceTreeChildObject(
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
                hasChildren: true // TODO
            ),
            $foundTraces
        );

        return new TraceTreeChildrenObjects(
            items: $traces,
            hasMore: $hasMore
        );
    }
}
