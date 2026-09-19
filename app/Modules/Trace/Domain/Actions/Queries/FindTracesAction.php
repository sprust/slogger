<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexParallelArraysException;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\Data\TraceDataObject;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use App\Modules\Trace\Entities\Trace\TraceItemObjects;
use App\Modules\Trace\Entities\Trace\TraceItemTraceObject;
use App\Modules\Trace\Entities\Trace\TraceServiceObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDto;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use Illuminate\Support\Arr;

readonly class FindTracesAction
{
    private const int TREE_IDS_BATCH_COUNT = 50000;

    private int $maxPerPage;

    public function __construct(
        private TraceRepository $traceRepository,
        private TraceTreeRepository $traceTreeRepository,
        private TraceTreeCacheRepository $traceTreeCacheRepository,
        private FindTraceServicesAction $findTraceServicesAction,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer
    ) {
        $this->maxPerPage = 20;
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexParallelArraysException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexNotInitException
     */
    public function handle(TraceFindParameters $parameters): TraceItemObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $traceIds = null;

        $treeRootTraceId = null;

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

                if ($parentTraceId === null) {
                    $traceIds = [];
                } elseif ($this->traceTreeCacheRepository->existsByRootTraceId($parentTraceId)) {
                    $treeRootTraceId = $parentTraceId;

                    $traceIds = [$parentTraceId];
                } else {
                    $traceIds[] = $parentTraceId;

                    $treeTraceIds = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId(
                        traceId: $parentTraceId,
                        batchCount: 300
                    );

                    foreach ($treeTraceIds as $treeTraceIdsChunk) {
                        foreach ($treeTraceIdsChunk as $treeTraceId) {
                            $traceIds[] = $treeTraceId;
                        }
                    }
                }
            }
        }

        $traceIds = ($traceIds === null) ? null : array_filter($traceIds);

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

        $tracesDto = is_null($treeRootTraceId)
            ? $this->search(
                parameters: $parameters,
                traceIds: $traceIds,
                page: $parameters->page,
                perPage: $perPage
            )
            : $this->searchInTree(
                parameters: $parameters,
                rootTraceId: $treeRootTraceId,
                perPage: $perPage
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
                        additionalFields: $parameters->data?->fields ?: []
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
     * @param string[]|null $traceIds
     *
     * @return TraceDto[]
     */
    private function search(
        TraceFindParameters $parameters,
        ?array $traceIds,
        int $page,
        int $perPage
    ): array {
        return $this->traceRepository->find(
            page: $page,
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
    }

    /**
     * @return TraceDto[]
     */
    private function searchInTree(
        TraceFindParameters $parameters,
        string $rootTraceId,
        int $perPage
    ): array {
        $needed = $parameters->page * $perPage;

        $found = [];

        $traceIdsBatches = $this->traceTreeCacheRepository->findTraceIds(
            rootTraceId: $rootTraceId,
            batchCount: self::TREE_IDS_BATCH_COUNT
        );

        foreach ($traceIdsBatches as $traceIdsBatch) {
            $found = $this->mergeFound(
                left: $found,
                right: $this->search(
                    parameters: $parameters,
                    traceIds: $traceIdsBatch,
                    page: 1,
                    perPage: $needed
                ),
                limit: $needed
            );
        }

        return array_slice($found, ($parameters->page - 1) * $perPage, $perPage);
    }

    /**
     * @param TraceDto[] $left
     * @param TraceDto[] $right
     *
     * @return TraceDto[]
     */
    private function mergeFound(array $left, array $right, int $limit): array
    {
        $merged = [];

        $leftIndex  = 0;
        $rightIndex = 0;

        $leftCount  = count($left);
        $rightCount = count($right);

        while (count($merged) < $limit && ($leftIndex < $leftCount || $rightIndex < $rightCount)) {
            if ($leftIndex >= $leftCount) {
                $merged[] = $right[$rightIndex++];

                continue;
            }

            if ($rightIndex >= $rightCount) {
                $merged[] = $left[$leftIndex++];

                continue;
            }

            $merged[] = $this->isEarlierInOrder($left[$leftIndex], $right[$rightIndex])
                ? $left[$leftIndex++]
                : $right[$rightIndex++];
        }

        return $merged;
    }

    private function isEarlierInOrder(TraceDto $left, TraceDto $right): bool
    {
        $leftLoggedAt  = $left->loggedAt->getTimestampMs();
        $rightLoggedAt = $right->loggedAt->getTimestampMs();

        if ($leftLoggedAt !== $rightLoggedAt) {
            return $leftLoggedAt > $rightLoggedAt;
        }

        return strcmp($left->id, $right->id) <= 0;
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
            $currentData     = null;
            $currentChildren = $data->children ?: [];
            $currentKey      = '';

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
