<?php

namespace App\Modules\TraceCollector\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceCollector\Domain\Entities\Objects\TraceTreeShortObject;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeFindParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\UTCDateTime;

class TraceRepository implements TraceRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $timestamp = new UTCDateTime(now());

        $operations = [];

        foreach ($parametersList->getItems() as $parameters) {
            $operations[] = [
                'updateOne' => [
                    [
                        'serviceId' => $parameters->serviceId,
                        'traceId'   => $parameters->traceId,
                    ],
                    [
                        '$set'         => [
                            'parentTraceId' => $parameters->parentTraceId,
                            'type'          => $parameters->type,
                            'status'        => $parameters->status,
                            'tags'          => $parameters->tags,
                            'data'          => json_decode($parameters->data, true),
                            'duration'      => $parameters->duration,
                            'memory'        => $parameters->memory,
                            'cpu'           => $parameters->cpu,
                            'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                            'updatedAt'     => $timestamp,
                        ],
                        '$setOnInsert' => [
                            'createdAt' => $timestamp,
                        ],
                    ],
                    [
                        'upsert' => true,
                    ],
                ],
            ];
        }

        Trace::collection()->bulkWrite($operations);
    }

    public function updateMany(TraceUpdateParametersList $parametersList): int
    {
        $timestamp = new UTCDateTime(now());

        $operations = [];

        foreach ($parametersList->getItems() as $parameters) {
            $operations[] = [
                'updateOne' => [
                    [
                        'serviceId' => $parameters->serviceId,
                        'traceId'   => $parameters->traceId,
                    ],
                    [
                        '$set' => [
                            'status'    => $parameters->status,
                            ...(is_null($parameters->profiling)
                                ? []
                                : [
                                    'profiling' => $parameters->profiling->getItems(),
                                ]),
                            ...(is_null($parameters->tags)
                                ? []
                                : [
                                    'tags' => $parameters->tags,
                                ]),
                            ...(is_null($parameters->data)
                                ? []
                                : [
                                    'data' => json_decode($parameters->data, true),
                                ]),
                            ...(is_null($parameters->duration)
                                ? []
                                : [
                                    'duration' => $parameters->duration,
                                ]),
                            ...(is_null($parameters->memory)
                                ? []
                                : [
                                    'memory' => $parameters->memory,
                                ]),
                            ...(is_null($parameters->cpu)
                                ? []
                                : [
                                    'cpu' => $parameters->cpu,
                                ]),
                            'updatedAt' => $timestamp,
                        ],
                    ],
                ],
            ];
        }

        return Trace::collection()->bulkWrite($operations)->getModifiedCount();
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
