<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Dto\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTraceObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTypeObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TraceAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Dto\Parameters\PeriodParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use MongoDB\BSON\UTCDateTime;

class TraceRepository implements TraceRepositoryInterface
{
    private int $maxPerPage = 20;

    public function __construct(
        // TODO
        private readonly TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    public function findOneByTraceId(string $traceId): ?TraceDetailObject
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $traceId)->first();

        if (!$trace) {
            return null;
        }

        return TraceDetailObject::fromModel($trace);
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

        $builder = $this->makeBuilder(
            traceIds: $traceIds,
            loggingPeriod: $parameters->loggingPeriod,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
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
                trace: TraceItemTraceObject::fromModel($parent, $parameters->data?->fields ?? []),
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

    public function findTypes(TraceFindTypesParameters $parameters): array
    {
        return $this
            ->makeBuilder(
                loggingPeriod: $parameters->loggingPeriod,
                data: $parameters->data,
            )
            ->when(
                $parameters->text,
                fn(Builder $query) => $query->where('type', 'like', "%$parameters->text%")
            )
            ->groupBy('type')
            ->pluck('type')
            ->sort()
            ->toArray();
    }

    public function findTags(TraceFindTagsParameters $parameters): array
    {
        $mql = $this
            ->makeBuilder(
                loggingPeriod: $parameters->loggingPeriod,
                types: $parameters->types,
                data: $parameters->data,
            )
            ->toMql();

        $match = [];

        foreach ($mql['find'][0] ?? [] as $key => $value) {
            $match[$key] = $value;
        }

        $pipeline = [];

        if ($match) {
            $pipeline[] = [
                '$match' => $match,
            ];
        }

        $pipeline[] = [
            '$unwind' => [
                'path' => '$tags',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id' => '$tags',
            ],
        ];

        if ($parameters->text) {
            $pipeline[] = [
                '$match' => [
                    '_id' => [
                        '$regex' => "^.*$parameters->text.*$",
                    ],
                ],
            ];
        }

        $pipeline[] = [
            '$limit' => 50,
        ];

        $iterator = Trace::collection()->aggregate($pipeline);

        return collect($iterator)->pluck('_id')->sort()->toArray();
    }

    public function findStatuses(TraceFindStatusesParameters $parameters): array
    {
        return $this
            ->makeBuilder(
                loggingPeriod: $parameters->loggingPeriod,
                types: $parameters->types,
                tags: $parameters->tags,
                data: $parameters->data,
            )
            ->when(
                $parameters->text,
                fn(Builder $query) => $query->where('status', 'like', "%$parameters->text%")
            )
            ->groupBy('status')
            ->pluck('status')
            ->sort()
            ->toArray();
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
     * @return Builder|Trace
     */
    private function makeBuilder(
        ?array $traceIds = null,
        ?PeriodParameters $loggingPeriod = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?TraceDataFilterParameters $data = null,
    ): Builder {
        $loggedAtFrom = $loggingPeriod?->from;
        $loggedAtTo   = $loggingPeriod?->to;

        $builder = Trace::query()
            ->when($traceIds, fn(Builder $query) => $query->whereIn('traceId', $traceIds))
            ->when($loggedAtFrom, fn(Builder $query) => $query->where('loggedAt', '>=', $loggedAtFrom))
            ->when($loggedAtTo, fn(Builder $query) => $query->where('loggedAt', '<=', $loggedAtTo))
            ->when($types, fn(Builder $query) => $query->whereIn('type', $types))
            ->when($tags, fn(Builder $query) => $query->where('tags', 'all', $tags))
            ->when($statuses, fn(Builder $query) => $query->whereIn('status', $statuses));

        return $this->applyDataFilter($builder, $data?->filter ?? []);
    }

    /**
     * @param TraceDataFilterItemParameters[] $filter
     */
    private function applyDataFilter(Builder $builder, array $filter): Builder
    {
        foreach ($filter as $filterItem) {
            $field = $filterItem->field;

            if (!is_null($filterItem->null)) {
                $filterItem->null
                    ? $builder->whereNull($field)
                    : $builder->whereNotNull($field);

                continue;
            }

            if (!is_null($filterItem->numeric)) {
                $builder->where(
                    column: $field,
                    operator: $filterItem->numeric->comp->value,
                    value: $filterItem->numeric->value
                );

                continue;
            }

            if (!is_null($filterItem->string)) {
                switch ($filterItem->string->comp) {
                    case TraceDataFilterCompStringTypeEnum::Con:
                        $pre  = '%';
                        $post = '%';
                        break;
                    case TraceDataFilterCompStringTypeEnum::Starts:
                        $pre  = '';
                        $post = '%';
                        break;
                    case TraceDataFilterCompStringTypeEnum::Ends:
                        $pre  = '%';
                        $post = '';
                        break;
                    default:
                        $pre  = '';
                        $post = '';
                        break;
                }

                $builder->where(
                    column: $field,
                    operator: 'like',
                    value: "$pre{$filterItem->string->value}$post"
                );

                continue;
            }

            if (!is_null($filterItem->boolean)) {
                $builder->where($field, $filterItem->boolean);
            }
        }

        return $builder;
    }
}
