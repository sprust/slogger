<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Trace\Contracts\Actions\Queries\FindTracesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\TraceDetailObject;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Entities\Trace\TraceItemTraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Entities\Trace\TraceTypeCountedObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Modules\Trace\Repositories\Services\TraceDynamicIndexInitializer;
use Illuminate\Support\Arr;

readonly class FindTracesAction implements FindTracesActionInterface
{
    private int $maxPerPage;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
        $this->maxPerPage = 20;
    }

    public function handle(TraceFindParameters $parameters): TraceItemObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $traceIds = null;

        if ($parameters->traceId) {
            $trace = $this->traceRepository->findOneDetailByTraceId($parameters->traceId);

            if (!$trace) {
                return new TraceItemObjects(
                    items: [],
                    paginationInfo: new PaginationInfoObject(
                        total: 0,
                        perPage: $perPage,
                        currentPage: 1
                    )
                );
            }

            if (!$parameters->allTracesInTree) {
                $traceIds = [
                    $parameters->traceId,
                ];
            } else {
                $parentTraceId = $this->traceTreeRepository->findParentTraceId($trace->traceId);

                $traceIds = $parentTraceId
                    ? $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId($parentTraceId)
                    : [];

                $traceIds[] = $parentTraceId;
            }
        }

        $this->traceDynamicIndexInitializer->init(
            serviceIds: $parameters->serviceIds,
            traceIds: $traceIds,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
            sort: $parameters->sort,
        );

        $traceItemsPagination = $this->traceRepository->find(
            page: $parameters->page,
            perPage: $perPage,
            serviceIds: $parameters->serviceIds,
            traceIds: $traceIds,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
            sort: $parameters->sort,
        );

        $traceTypeCounts = empty($traceItemsPagination->items)
            ? []
            : $this->traceRepository->findTypeCounts(
                traceIds: array_map(
                    fn(TraceDetailObject $traceDto) => $traceDto->traceId,
                    $traceItemsPagination->items
                )
            );

        /** @var TraceTypeCountedObject[] $groupedTypeCounts */
        $groupedTypeCounts = collect($traceTypeCounts)
            ->groupBy(fn(TraceTypeCountedObject $countedTraceType) => $countedTraceType->traceId)
            ->toArray();

        $resultItems = [];

        foreach ($traceItemsPagination->items as $trace) {
            /** @var TraceTypeCountedObject[] $types */
            $types = $groupedTypeCounts[$trace->traceId] ?? [];

            $resultItems[] = new TraceItemObject(
                trace: new TraceItemTraceObject(
                    service: $trace->service
                        ? new TraceServiceObject(
                            id: $trace->service->id,
                            name: $trace->service->name,
                        )
                        : null,
                    traceId: $trace->traceId,
                    parentTraceId: $trace->parentTraceId,
                    type: $trace->type,
                    status: $trace->status,
                    tags: $trace->tags,
                    duration: $trace->duration,
                    memory: $trace->memory,
                    cpu: $trace->cpu,
                    hasProfiling: $trace->hasProfiling,
                    additionalFields: $this->makeTraceAdditionalFields(
                        data: $trace->data,
                        additionalFields: $parameters->data?->fields ?? []
                    ),
                    loggedAt: $trace->loggedAt,
                    createdAt: $trace->createdAt,
                    updatedAt: $trace->updatedAt
                ),
                types: $types
            );
        }

        return new TraceItemObjects(
            items: $resultItems,
            paginationInfo: $traceItemsPagination->paginationInfo,
        );
    }

    /**
     * @return TraceDataAdditionalFieldObject[]
     */
    private function makeTraceAdditionalFields(TraceDataObject $data, array $additionalFields): array
    {
        $additionalFieldValues = [];

        foreach ($additionalFields as $additionalField) {
            $currentData = null;
            $currentChildren = $data->children;
            $currentKey = '';

            foreach (explode('.', $additionalField) as $key) {
                $currentKey .= (($currentKey ? '.' : '') . $key);

                $currentData = Arr::first(
                    $currentChildren,
                    fn(TraceDataObject $child) => $child->key === $currentKey
                );

                if (!$currentData) {
                    break;
                }

                if (!$currentData->children) {
                    break;
                }

                $currentChildren = $currentData->children;
            }

            if (!$currentData) {
                continue;
            }

            $additionalFieldValues[] = new TraceDataAdditionalFieldObject(
                key: $additionalField,
                values: [
                    $currentData->value,
                ]
            );
        }

        return $additionalFieldValues;
    }
}
