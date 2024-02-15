<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObject;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentTypeObject;
use App\Modules\TracesAggregator\Dto\Objects\TraceParentTypeObjects;
use App\Modules\TracesAggregator\Dto\Parameters\DataFilter\TraceDataFilterItemParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceParentTypesParameters;
use App\Modules\TracesAggregator\Dto\TraceObject;
use App\Modules\TracesAggregator\Enums\TraceDataFilterCompStringTypeEnum;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use MongoDB\BSON\UTCDateTime;

class TraceParentsRepository implements TraceParentsRepositoryInterface
{
    private int $maxPerPage = 20;

    public function findParentTypes(TraceParentTypesParameters $parameters): TraceParentTypeObjects
    {
        $pipeline = [];

        $match = [
            'parentTraceId' => null,
        ];

        $loggedAtFrom = $parameters->loggingPeriod?->from;
        $loggedAtTo   = $parameters->loggingPeriod?->to;

        if ($loggedAtFrom || $loggedAtTo) {
            $match['loggedAt'] = [
                ...($loggedAtFrom ? ['$gte' => new UTCDateTime($loggedAtFrom)] : []),
                ...($loggedAtTo ? ['$lte' => new UTCDateTime($loggedAtTo)] : []),
            ];
        }

        $pipeline[] = [
            '$match' => $match,
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => [
                    'type' => '$type',
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

        $limit = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);
        $skip  = ($parameters->page - 1) * $limit;

        $pipeline[] = [
            '$facet' => [
                'result' => [
                    [
                        '$limit' => $limit,
                    ],
                    [
                        '$skip' => $skip,
                    ],
                ],
                'count'  => [
                    [
                        '$count' => 'count',
                    ],
                ],
            ],
        ];

        $typesAggregation = collect(Trace::collection()->aggregate($pipeline));

        $resultItems = [];

        foreach ($typesAggregation[0]->result as $item) {
            $resultItems[] = new TraceParentTypeObject(
                type: $item->_id->type,
                count: $item->count,
            );
        }

        return new TraceParentTypeObjects(
            items: $resultItems,
            paginationInfo: new PaginationInfoObject(
                total: $typesAggregation[0]->count[0]?->count ?? 0,
                perPage: $limit,
                currentPage: $parameters->page,
            ),
        );
    }

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $loggedAtFrom = $parameters->loggingPeriod?->from;
        $loggedAtTo   = $parameters->loggingPeriod?->to;

        $data = $parameters->data;

        $builder = Trace::query()
            ->when($loggedAtFrom, fn(Builder $query) => $query->where('loggedAt', '>=', $loggedAtFrom))
            ->when($loggedAtTo, fn(Builder $query) => $query->where('loggedAt', '<=', $loggedAtTo))
            ->when(
                $parameters->types,
                fn(Builder $query) => $query->whereIn('type', $parameters->types)
            )
            ->when(
                $parameters->tags,
                fn(Builder $query) => $query->where('tags', 'all', $parameters->tags)
            )
            ->when(
                count($parameters->sort),
                function (Builder $query) use ($parameters) {
                    foreach ($parameters->sort as $sortItem) {
                        $query->orderBy($sortItem->field, $sortItem->directionEnum->value);
                    }
                }
            );

        $parentsPaginator = $this->applyDataFilter($builder, $parameters->data->filter)
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
                trace: TraceObject::fromModel($parent, $data?->fields ?? []),
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
