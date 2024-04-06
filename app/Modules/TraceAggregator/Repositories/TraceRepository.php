<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\DataFilter\TraceDataFilterParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDetailDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceItemsPaginationDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceServiceDto;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTypeDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceAggregator\Repositories\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

readonly class TraceRepository implements TraceRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function findOneByTraceId(string $traceId): ?TraceDetailDto
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $traceId)->first();

        if (!$trace) {
            return null;
        }

        return new TraceDetailDto(
            id: $trace->_id,
            service: $trace->service
                ? new TraceServiceDto(
                    id: $trace->service->id,
                    name: $trace->service->name,
                )
                : null,
            traceId: $trace->traceId,
            parentTraceId: $trace->parentTraceId,
            type: $trace->type,
            status: $trace->status,
            tags: $trace->tags,
            data: $trace->data,
            duration: $trace->duration,
            memory: $trace->memory,
            cpu: $trace->cpu,
            loggedAt: $trace->loggedAt,
            createdAt: $trace->createdAt,
            updatedAt: $trace->updatedAt
        );
    }

    public function find(
        int $page = 1,
        int $perPage = 20,
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        // TODO
        ?array $sort = null,
        // TODO
        ?TraceDataFilterParameters $data = null,
    ): TraceItemsPaginationDto {
        $builder = $this->traceQueryBuilder->make(
            serviceIds: $serviceIds,
            traceIds: $traceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            tags: $tags,
            statuses: $statuses,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            data: $data,
        );

        $tracesPaginator = $builder
            ->with([
                'service',
            ])
            ->when(
                !is_null($sort),
                function (Builder $query) use ($sort) {
                    foreach ($sort as $sortItem) {
                        $query->orderBy($sortItem->field, $sortItem->directionEnum->value);
                    }
                }
            )
            ->paginate(
                perPage: $perPage,
                page: $page
            );

        /** @var Trace[] $traces */
        $traces = $tracesPaginator->items();

        return new TraceItemsPaginationDto(
            items: array_map(
                fn(Trace $trace) => new TraceDetailDto(
                    id: $trace->_id,
                    service: $trace->service
                        ? new TraceServiceDto(
                            id: $trace->service->id,
                            name: $trace->service->name,
                        )
                        : null,
                    traceId: $trace->traceId,
                    parentTraceId: $trace->parentTraceId,
                    type: $trace->type,
                    status: $trace->status,
                    tags: $trace->tags,
                    data: $trace->data,
                    duration: $trace->duration,
                    memory: $trace->memory,
                    cpu: $trace->cpu,
                    loggedAt: $trace->loggedAt,
                    createdAt: $trace->createdAt,
                    updatedAt: $trace->updatedAt
                ),
                $traces
            ),
            paginationInfo: new PaginationInfoObject(
                total: $tracesPaginator->total(),
                perPage: $perPage,
                currentPage: $page,
            )
        );
    }

    public function findByTraceIds(array $traceIds): array
    {
        /** @var TraceDto[] $children */
        $children = [];

        foreach (collect($traceIds)->chunk(1000) as $childrenIdsChunk) {
            Trace::query()
                ->select([
                    '_id',
                    'serviceId',
                    'traceId',
                    'parentTraceId',
                    'type',
                    'status',
                    'tags',
                    'duration',
                    'memory',
                    'cpu',
                    'loggedAt',
                    'createdAt',
                    'updatedAt',
                ])
                ->with([
                    'service' => fn(BelongsTo $relation) => $relation->select([
                        'id',
                        'name',
                    ]),
                ])
                ->whereIn('traceId', $childrenIdsChunk)
                ->each(function (Trace $trace) use (&$children) {
                    $children[] = new TraceDto(
                        id: $trace->_id,
                        service: $trace->service
                            ? new TraceServiceDto(
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
                        loggedAt: $trace->loggedAt,
                        createdAt: $trace->createdAt,
                        updatedAt: $trace->updatedAt
                    );
                });
        }

        return $children;
    }

    public function findTypeCounts(array $traceIds): array
    {
        $pipeline = [];

        $pipeline[] = [
            '$match' => [
                'parentTraceId' => [
                    '$in' => $traceIds,
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

        $typesAggregation = Trace::collection()->aggregate($pipeline);

        $types = [];

        foreach ($typesAggregation as $item) {
            $types[] = new TraceTypeDto(
                traceId: $item->_id->parentTraceId,
                type: $item->_id->type,
                count: $item->count,
            );
        }

        return $types;
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
