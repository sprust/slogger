<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDataAdditionalFieldObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemTraceObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Domain\Entities\Transports\TraceTypeTransport;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDetailDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTypeDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
use Illuminate\Support\Arr;

readonly class FindTracesAction
{
    private int $maxPerPage;

    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
    ) {
        $this->maxPerPage = 20;
    }

    public function handle(TraceFindParameters $parameters): TraceItemObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $traceIds = null;

        if ($parameters->traceId) {
            $trace = $this->traceRepository->findOneByTraceId($parameters->traceId);

            if (!$trace) {
                return new TraceItemObjects(
                    items: [],
                    paginationInfo: new PaginationInfoObject(
                        total: 0,
                        perPage: $perPage,
                        currentPage: 1,
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
            sort: $parameters->sort,
            data: $parameters->data,
        );

        $traceTypeCounts = empty($traceItemsPagination->items)
            ? []
            : $this->traceRepository->findTypeCounts(
                traceIds: array_map(
                    fn(TraceDetailDto $traceDto) => $traceDto->traceId,
                    $traceItemsPagination->items
                )
            );

        /** @var TraceTypeDto[] $groupedTypeCounts */
        $groupedTypeCounts = collect($traceTypeCounts)
            ->groupBy(fn(TraceTypeDto $traceTypeDto) => $traceTypeDto->traceId)
            ->toArray();

        $resultItems = [];

        foreach ($traceItemsPagination->items as $trace) {
            $types = array_map(
                fn(TraceTypeDto $item) => TraceTypeTransport::toObject($item),
                $groupedTypeCounts[$trace->traceId] ?? []
            );

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
    private function makeTraceAdditionalFields(array $data, array $additionalFields): array
    {
        $additionalFieldValues = [];

        foreach ($additionalFields as $additionalField) {
            $additionalFieldData = explode('.', $additionalField);

            if (count($additionalFieldData) === 1) {
                $values = [Arr::get($data, $additionalField)];
            } else {
                $preKey = implode('.', array_slice($additionalFieldData, 0, -1));

                $preValue = Arr::get($data, $preKey);

                if (is_null($preValue)) {
                    continue;
                }

                if (Arr::isAssoc($preValue)) {
                    $values = [Arr::get($data, $additionalField)];
                } else {
                    $key = $additionalFieldData[count($additionalFieldData) - 1];

                    $values = array_filter(
                        array_map(
                            fn(array $item) => $item[$key] ?? null,
                            $preValue
                        )
                    );
                }
            }

            $additionalFieldValues[] = new TraceDataAdditionalFieldObject(
                key: $additionalField,
                values: $values
            );
        }

        return $additionalFieldValues;
    }
}
