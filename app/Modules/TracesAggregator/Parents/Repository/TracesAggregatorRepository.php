<?php

namespace App\Modules\TracesAggregator\Parents\Repository;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentObjects;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentTypeObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentTypeObjects;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceObject;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TracesAggregatorParentsParameters;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TracesAggregatorParentTypesParameters;
use App\Modules\TracesAggregator\Parents\Enums\TracesAggregatorParentsSortFieldEnum;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TracesAggregatorRepository implements TracesAggregatorRepositoryInterface
{
    private int $maxPerPage = 20;

    public function findParentTypes(
        TracesAggregatorParentTypesParameters $parameters
    ): TracesAggregatorParentTypeObjects {
        $pipeline = [];

        $pipeline[] = [
            '$match' => [
                'parentTraceId' => null,
            ],
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
            $resultItems[] = new TracesAggregatorParentTypeObject(
                type: $item->_id->type,
                count: $item->count,
            );
        }

        return new TracesAggregatorParentTypeObjects(
            items: $resultItems,
            paginationInfo: new PaginationInfoObject(
                total: $typesAggregation[0]->count[0]->count,
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }

    public function findParents(TracesAggregatorParentsParameters $parameters): TracesAggregatorParentObjects
    {
        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $parentsPaginator = Trace::query()
            ->whereNull('parentTraceId')
            ->when(
                !is_null($parameters->type),
                fn(Builder $query) => $query->where('type', $parameters->type)
            )
            ->when(
                count($parameters->sort),
                function (Builder $query) use ($parameters) {
                    foreach ($parameters->sort as $sortItem) {
                        $field = match ($sortItem->fieldEnum) {
                            TracesAggregatorParentsSortFieldEnum::CreatedAt => 'createdAt'
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
                    return new TracesAggregatorParentTypeObject(
                        type: $item->_id->type,
                        count: $item->count,
                    );
                })->toArray()
                ?? [];

            $resultItems[] = new TracesAggregatorParentObject(
                parent: TraceObject::fromModel($parent),
                types: $types
            );
        }

        return new TracesAggregatorParentObjects(
            items: $resultItems,
            paginationInfo: new PaginationInfoObject(
                total: $parentsPaginator->total(),
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }
}
