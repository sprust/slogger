<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Dto\Objects\TraceTreeShortObject;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindStatusesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTagsParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceFindTypesParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeFindParameters;
use App\Modules\TraceAggregator\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\UTCDateTime;

readonly class TraceContentRepository implements TraceContentRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function findTypes(TraceFindTypesParameters $parameters): array
    {
        return $this->traceQueryBuilder
            ->make(
                serviceIds: $parameters->serviceIds,
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
        $mql = $this->traceQueryBuilder
            ->make(
                serviceIds: $parameters->serviceIds,
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
        return $this->traceQueryBuilder
            ->make(
                serviceIds: $parameters->serviceIds,
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
}
