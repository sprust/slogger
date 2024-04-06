<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDataAdditionalFieldObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemTraceObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceItemTypeObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Services\TraceQueryBuilder;
use App\Modules\TraceAggregator\Services\TraceDataToObjectConverter;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use MongoDB\BSON\UTCDateTime;

readonly class TraceRepository implements TraceRepositoryInterface
{
    private int $maxPerPage;

    public function __construct(
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private TraceQueryBuilder $traceQueryBuilder
    ) {
        $this->maxPerPage = 20;
    }

    public function findOneByTraceId(string $traceId): ?TraceDetailObject
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $traceId)->first();

        if (!$trace) {
            return null;
        }

        return new TraceDetailObject(
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
            data: (new TraceDataToObjectConverter($trace->data))->convert(),
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }

    public function find(TraceFindParameters $parameters): TraceItemObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $traceIds = null;

        if ($parameters->traceId) {
            /** @var Trace|null $trace */
            $trace = Trace::query()->where('traceId', $parameters->traceId)->first();

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
                $traceIds = [$parameters->traceId];
            } else {
                $parentTrace = $this->traceTreeRepository->findParentTrace($trace);

                $traceIds   = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId($parentTrace);
                $traceIds[] = $parentTrace->traceId;
            }
        }

        $builder = $this->traceQueryBuilder->make(
            serviceIds: $parameters->serviceIds,
            traceIds: $traceIds,
            loggingPeriod: $parameters->loggingPeriod,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            data: $parameters->data,
        );

        $parentsPaginator = $builder
            ->with([
                'service',
            ])
            ->when(
                count($parameters->sort),
                function (Builder $query) use ($parameters) {
                    foreach ($parameters->sort as $sortItem) {
                        $query->orderBy($sortItem->field, $sortItem->directionEnum->value);
                    }
                }
            )
            ->paginate(
                perPage: $perPage,
                page: $parameters->page
            );

        /** @var Collection|Trace[] $parents */
        $parents = $parentsPaginator->items();

        $pipeline = [];

        $pipeline[] = [
            '$match' => [
                'parentTraceId' => [
                    '$in' => collect($parents)->pluck('traceId')->toArray(),
                ],
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => [
                    'parentTraceId' => '$parentTraceId',
                    'type'          => '$type',
                ],
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $pipeline[] = [
            '$project' => [
                '_id'   => 1,
                'count' => 1,
            ],
        ];

        $pipeline[] = [
            '$sort' => [
                'count'    => -1,
                '_id.type' => 1,
            ],
        ];

        $typesAggregation = collect(Trace::collection()->aggregate($pipeline))
            ->groupBy(function (object $item) {
                return $item->_id->parentTraceId;
            });

        $resultItems = [];

        foreach ($parents as $parent) {
            $types = $typesAggregation->get($parent->traceId)
                ?->map(function (object $item) {
                    return new TraceItemTypeObject(
                        type: $item->_id->type,
                        count: $item->count,
                    );
                })->toArray()
                ?? [];

            $resultItems[] = new TraceItemObject(
                trace: new TraceItemTraceObject(
                    service: $parent->service
                        ? new TraceServiceObject(
                            id: $parent->service->id,
                            name: $parent->service->name,
                        )
                        : null,
                    traceId: $parent->traceId,
                    parentTraceId: $parent->parentTraceId,
                    type: $parent->type,
                    status: $parent->status,
                    tags: $parent->tags,
                    duration: $parent->duration,
                    memory: $parent->memory,
                    cpu: $parent->cpu,
                    additionalFields: $this->makeTraceAdditionalFields(
                        data: $parent->data,
                        additionalFields: $parameters->data?->fields ?? []
                    ),
                    loggedAt: $parent->loggedAt,
                    createdAt: $parent->createdAt,
                    updatedAt: $parent->updatedAt
                ),
                types: $types
            );
        }

        return new TraceItemObjects(
            items: $resultItems,
            paginationInfo: new PaginationInfoObject(
                total: $parentsPaginator->total(),
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }

    public function findTree(TraceTreeFindParameters $parameters): array
    {
        return Trace::query()
            ->select([
                'traceId',
                'parentTraceId',
                'loggedAt',
            ])
            ->when(
                $parameters->to,
                fn(Builder $query) => $query->where('loggedAt', '<=', new UTCDateTime($parameters->to))
            )
            ->forPage(
                page: $parameters->page,
                perPage: $parameters->perPage
            )
            ->get()
            ->map(
                fn(Trace $trace) => new TraceTreeShortObject(
                    traceId: $trace->traceId,
                    parentTraceId: $trace->parentTraceId,
                    loggedAt: $trace->loggedAt
                )
            )
            ->toArray();
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
