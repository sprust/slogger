<?php

namespace App\Modules\TracesAggregator\Parents\Repository;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\TraceObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceParentObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceParentObjects;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceParentTypeObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceParentTypeObjects;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TraceParentsFindParameters;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TraceParentTypesParameters;
use App\Modules\TracesAggregator\Parents\Enums\TraceParentsSortFieldEnum;
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

        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);
        $skip    = ($parameters->page - 1) * $perPage;

        $pipeline[] = [
            '$facet' => [
                'result' => [
                    [
                        '$limit' => $perPage,
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
                total: $typesAggregation[0]->count[0]->count,
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }

    public function findParents(TraceParentsFindParameters $parameters): TraceParentObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $loggedAtFrom = $parameters->loggingPeriod?->from;
        $loggedAtTo   = $parameters->loggingPeriod?->to;

        $parentsPaginator = Trace::query()
            ->whereNull('parentTraceId')
            ->when(
                !is_null($parameters->type),
                fn(Builder $query) => $query->where('type', $parameters->type)
            )
            ->when($loggedAtFrom, fn(Builder $query) => $query->where('loggedAt', '>=', $loggedAtFrom))
            ->when($loggedAtTo, fn(Builder $query) => $query->where('loggedAt', '<=', $loggedAtTo))
            ->when(
                count($parameters->sort),
                function (Builder $query) use ($parameters) {
                    foreach ($parameters->sort as $sortItem) {
                        $field = match ($sortItem->fieldEnum) {
                            TraceParentsSortFieldEnum::LoggedAt => 'loggedAt'
                        };

                        $query->orderBy($field, $sortItem->directionEnum->value);
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
                parent: TraceObject::fromModel($parent),
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
}
