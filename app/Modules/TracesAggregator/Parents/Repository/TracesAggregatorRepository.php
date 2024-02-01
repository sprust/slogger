<?php

namespace App\Modules\TracesAggregator\Parents\Repository;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Enums\TracesAggregatorSortFieldEnum;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentObjects;
use App\Modules\TracesAggregator\Parents\Dto\Objects\Parents\TracesAggregatorParentTypeObject;
use App\Modules\TracesAggregator\Parents\Dto\Objects\TraceObject;
use App\Modules\TracesAggregator\Parents\Dto\Parameters\TracesAggregatorParentsParameters;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TracesAggregatorRepository implements TracesAggregatorRepositoryInterface
{
    private int $maxPerPage = 20;

    public function findParents(TracesAggregatorParentsParameters $parameters): TracesAggregatorParentObjects
    {
        $parentsBuilder = Trace::query()
            ->whereNull('parentTraceId')
            ->when(
                !is_null($parameters->type),
                fn(Builder $query) => $query->where('type', $parameters->type)
            );

        $perPage = min($parameters->perPage ?: $this->maxPerPage, $this->maxPerPage);

        $parentsBuilderForResult = $parentsBuilder->clone()
            ->when(
                count($parameters->sort),
                function (Builder $query) use ($parameters) {
                    foreach ($parameters->sort as $sortItem) {
                        $field = match ($sortItem->fieldEnum) {
                            TracesAggregatorSortFieldEnum::CreatedAt => 'createdAt'
                        };

                        $query->orderBy($field, $sortItem->directionEnum->value);
                    }
                }
            )
            ->forPage(
                $parameters->page,
                $perPage
            );

        /** @var Collection|Trace[] $parents */
        $parents = $parentsBuilderForResult->get();

        $pipeline = [];

        $pipeline[] = [
            '$match' => [
                'parentTraceId' => [
                    '$in' => $parents->pluck('traceId')->toArray(),
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
                'count' => -1,
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
                total: $parentsBuilder->count(),
                perPage: $perPage,
                currentPage: $parameters->page,
            ),
        );
    }
}
