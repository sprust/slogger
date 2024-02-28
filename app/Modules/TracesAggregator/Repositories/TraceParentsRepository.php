<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObject;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentTypeObject;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindByTextParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\PeriodParameters;
use App\Modules\TracesAggregator\Dto\TraceDetailObject;
use App\Modules\TracesAggregator\Dto\TraceObject;
use App\Modules\TracesAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TraceParentsRepository implements TraceParentsRepositoryInterface
{
    private int $maxPerPage = 20;

    public function __construct(
        // TODO
        private readonly TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    public function findByTraceId(string $traceId): ?TraceDetailObject
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $traceId)->first();

        if (!$trace) {
            return null;
        }

        return TraceDetailObject::fromModel($trace);
    }

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $traceIds = null;

        if ($parameters->traceId) {
            /** @var Trace|null $trace */
            $trace = Trace::query()->where('traceId', $parameters->traceId)->first();

            if (!$trace) {
                return new TraceParentObjects(
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
                    return new TraceParentTypeObject(
                        type: $item->_id->type,
                        count: $item->count,
                    );
                })->toArray()
                ?? [];

            $resultItems[] = new TraceParentObject(
                trace: TraceObject::fromModel($parent, $parameters->data?->fields ?? []),
                types: $types
            );
        }

        return new TraceParentObjects(
            items: $resultItems,
            paginationInfo: new PaginationInfoObject(
                total: $parentsPaginator->total(),
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }

    public function findTypes(TraceParentsFindByTextParameters $parameters): array
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

    public function findTags(TraceParentsFindByTextParameters $parameters): array
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

    /**
     * @return Builder|Trace
     */
    private function makeBuilder(
        ?array $traceIds = null,
        ?PeriodParameters $loggingPeriod = null,
        array $types = [],
        array $tags = [],
        ?TraceDataFilterParameters $data = null,
    ): Builder {
        $loggedAtFrom = $loggingPeriod?->from;
        $loggedAtTo   = $loggingPeriod?->to;

        $builder = Trace::query()
            ->when($traceIds, fn(Builder $query) => $query->whereIn('traceId', $traceIds))
            ->when($loggedAtFrom, fn(Builder $query) => $query->where('loggedAt', '>=', $loggedAtFrom))
            ->when($loggedAtTo, fn(Builder $query) => $query->where('loggedAt', '<=', $loggedAtTo))
            ->when(
                $types,
                fn(Builder $query) => $query->whereIn('type', $types)
            )
            ->when(
                $tags,
                fn(Builder $query) => $query->where('tags', 'all', $tags)
            );

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
