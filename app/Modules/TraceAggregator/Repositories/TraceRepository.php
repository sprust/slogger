<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Common\Pagination\PaginationInfoObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceDataAdditionalFieldObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTraceObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTypeObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceServiceObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Repositories\Dto\ServiceStatDto;
use App\Modules\TraceAggregator\Repositories\Dto\ServiceStatsPaginationDto;
use App\Modules\TraceAggregator\Services\TraceDataToObjectConverter;
use App\Modules\TraceAggregator\Services\TraceQueryBuilder;
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
        $perPage = $this->getPerPage($parameters->perPage);

        $builder = $this->makeBuilder($parameters);

        if (!$builder) {
            return new TraceItemObjects(
                items: [],
                paginationInfo: new PaginationInfoObject(
                    total: 0,
                    perPage: $perPage,
                    currentPage: 1,
                )
            );
        }

        $paginator = $builder
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
        $parents = $paginator->items();

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
                total: $paginator->total(),
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

    public function findServiceStat(TraceFindParameters $parameters): ServiceStatsPaginationDto
    {
        $perPage = $this->getPerPage($parameters->perPage);

        $builder = $this->makeBuilder($parameters);

        if (!$builder) {
            return new ServiceStatsPaginationDto(
                items: [],
                paginationInfo: new PaginationInfoObject(
                    total: 0,
                    perPage: $perPage,
                    currentPage: 1,
                )
            );
        }

        $mql = $builder->toMql();

        $match = [];

        foreach ($mql['find'][0] ?? [] as $key => $value) {
            $match[$key] = $value;
        }

        $dataPipeline       = [];
        $paginationPipeline = [];

        if ($match) {
            $dataPipeline[] = [
                '$match' => $match,
            ];
            $paginationPipeline[] = [
                '$match' => $match,
            ];
        }

        $dataPipeline[] = [
            '$unwind' => [
                'path' => '$tags',
            ],
        ];

        $dataPipeline[] = [
            '$group' => [
                '_id'   => [
                    'serviceId' => '$serviceId',
                    'type'      => '$type',
                    'status'    => '$status',
                    'tag'       => '$tags',
                ],
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $dataPipeline[] = [
            '$sort' => [
                'count' => -1,
            ],
        ];

        $dataPipeline[] = [
            '$skip' => $perPage * ($parameters->page - 1),
        ];

        $dataPipeline[] = [
            '$limit' => $perPage,
        ];

        $paginationPipeline[] = [
            '$group' => [
                '_id'   => null,
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $stats = [];

        $documents = Trace::collection()
            ->aggregate([
                [
                    '$facet' => [
                        'data'       => $dataPipeline,
                        'pagination' => $paginationPipeline,
                    ],
                ],
            ])
            ->toArray()[0]
            ?? null;

        if (is_null($documents)) {
            return new ServiceStatsPaginationDto(
                items: [],
                paginationInfo: new PaginationInfoObject(
                    total: 0,
                    perPage: $perPage,
                    currentPage: 1,
                )
            );
        }

        foreach ($documents->data as $document) {
            $stats[] = new ServiceStatDto(
                serviceId: $document->_id->serviceId,
                type: $document->_id->type,
                tag: $document->_id->tag,
                status: $document->_id->status,
                count: $document->count
            );
        }

        return new ServiceStatsPaginationDto(
            items: $stats,
            paginationInfo: new PaginationInfoObject(
                total: $documents->pagination[0]->count,
                perPage: $perPage,
                currentPage: $parameters->page,
            )
        );
    }

    private function getPerPage(?int $perPage): int
    {
        return min($perPage ?: $this->maxPerPage, $this->maxPerPage);
    }

    /**
     * @return Builder|\MongoDB\Laravel\Query\Builder|Trace|null
     */
    private function makeBuilder(TraceFindParameters $parameters): ?Builder
    {
        $traceIds = null;

        if ($parameters->traceId) {
            /** @var Trace|null $trace */
            $trace = Trace::query()->where('traceId', $parameters->traceId)->first();

            if (!$trace) {
                return null;
            }

            if (!$parameters->allTracesInTree) {
                $traceIds = [$parameters->traceId];
            } else {
                $parentTrace = $this->traceTreeRepository->findParentTrace($trace);

                $traceIds   = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId($parentTrace);
                $traceIds[] = $parentTrace->traceId;
            }
        }

        return $this->traceQueryBuilder->make(
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
