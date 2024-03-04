<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Dto\Objects\TraceDetailObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemObjects;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTraceObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceItemTypeObject;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Services\TraceQueryBuilder;
use App\Services\Dto\PaginationInfoObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

        $builder = $this->traceQueryBuilder->make(
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
}
