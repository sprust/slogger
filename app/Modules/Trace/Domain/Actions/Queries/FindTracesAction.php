<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Trace\Contracts\Actions\Queries\FindTracesActionInterface;
use App\Modules\Trace\Contracts\Actions\Queries\FindTraceServicesActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Entities\Trace\TraceItemTraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use Illuminate\Support\Arr;

readonly class FindTracesAction implements FindTracesActionInterface
{
    private int $maxPerPage;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private FindTraceServicesActionInterface $findTraceServicesAction,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
        $this->maxPerPage = 20;
    }

    public function handle(TraceFindParameters $parameters): TraceItemObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $traceIds = null;

        if ($parameters->traceId) {
            $traceDto = $this->traceRepository->findOneDetailByTraceId($parameters->traceId);

            if (!$traceDto) {
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
                $parentTraceId = $this->traceTreeRepository->findParentTraceId($traceDto->traceId);

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
            needLoggedAt: true,
        );

        $tracesDto = $this->traceRepository->find(
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
        );

        $serviceIds = array_unique(
            array_filter(
                array_map(
                    fn(TraceDto $traceDto) => $traceDto->serviceId,
                    $tracesDto
                )
            )
        );

        $traceServices = $this->findTraceServicesAction->handle(
            serviceIds: $serviceIds
        );

        $resultItems = [];

        foreach ($tracesDto as $traceDto) {
            $service = $traceDto->serviceId
                ? $traceServices->getById($traceDto->serviceId)
                : null;

            $resultItems[] = new TraceItemObject(
                trace: new TraceItemTraceObject(
                    service: $service
                        ? new TraceServiceObject(
                            id: $service->id,
                            name: $service->name,
                        )
                        : null,
                    traceId: $traceDto->traceId,
                    parentTraceId: $traceDto->parentTraceId,
                    type: $traceDto->type,
                    status: $traceDto->status,
                    tags: $traceDto->tags,
                    duration: $traceDto->duration,
                    memory: $traceDto->memory,
                    cpu: $traceDto->cpu,
                    hasProfiling: $traceDto->hasProfiling,
                    additionalFields: $this->makeTraceAdditionalFields(
                        data: $traceDto->data,
                        additionalFields: $parameters->data?->fields ?? []
                    ),
                    loggedAt: $traceDto->loggedAt,
                    createdAt: $traceDto->createdAt,
                    updatedAt: $traceDto->updatedAt
                )
            );
        }

        return new TraceItemObjects(
            items: $resultItems,
            paginationInfo: new PaginationInfoObject(
                total: 0,
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }

    /**
     * @param string[] $additionalFields
     *
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
