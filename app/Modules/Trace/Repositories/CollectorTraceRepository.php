<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Dto\TraceLoggedAtDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampMetricDto;
use App\Modules\Trace\Repositories\Dto\TraceTreeDto;
use App\Modules\Trace\Repositories\Interfaces\CollectorTraceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;

class CollectorTraceRepository implements CollectorTraceRepositoryInterface
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
                            'timestamps'    => $this->makeTimestampsData($parameters->timestamps),
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
            $hasProfiling = is_null($parameters->profiling) ? null : !empty($parameters->profiling->getItems());

            $operations[] = [
                'updateOne' => [
                    [
                        'serviceId' => $parameters->serviceId,
                        'traceId'   => $parameters->traceId,
                    ],
                    [
                        '$set' => [
                            'status'    => $parameters->status,
                            ...(is_null($hasProfiling)
                                ? []
                                : [
                                    'hasProfiling' => $hasProfiling,
                                ]),
                            ...(is_null($parameters->profiling)
                                ? []
                                : [
                                    'profiling' => [
                                        'mainCaller' => $parameters->profiling->getMainCaller(),
                                        'items'      => $parameters->profiling->getItems(),
                                    ],
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

    public function findTree(int $page = 1, int $perPage = 15, ?Carbon $to = null): array
    {
        return Trace::query()
            ->select([
                'traceId',
                'parentTraceId',
                'loggedAt',
            ])
            ->when(
                $to,
                fn(Builder $query) => $query->where('loggedAt', '<=', new UTCDateTime($to))
            )
            ->forPage(
                page: $page,
                perPage: $perPage
            )
            ->get()
            ->map(
                fn(Trace $trace) => new TraceTreeDto(
                    traceId: $trace->traceId,
                    parentTraceId: $trace->parentTraceId,
                    loggedAt: $trace->loggedAt
                )
            )
            ->toArray();
    }

    public function findLoggedAtList(int $page, int $perPage, Carbon $loggedAtTo): array
    {
        return Trace::query()
            ->select([
                'traceId',
                'loggedAt',
            ])
            ->where('loggedAt', '<=', $loggedAtTo)
            ->orderBy('_id')
            ->forPage(page: $page, perPage: $perPage)
            ->get()
            ->map(
                fn(Trace $trace) => new TraceLoggedAtDto(
                    traceId: $trace->traceId,
                    loggedAt: $trace->loggedAt
                )
            )
            ->toArray();
    }

    public function updateTraceTimestamps(string $traceId, array $timestamps): void
    {
        Trace::query()
            ->where('traceId', $traceId)
            ->update([
                'timestamps' => $this->makeTimestampsData($timestamps),
            ]);
    }

    /**
     * @param TraceTimestampMetricDto[] $timestamps
     */
    private function makeTimestampsData(array $timestamps): array
    {
        $result = [];

        foreach ($timestamps as $timestamp) {
            $result[$timestamp->key] = new UTCDateTime($timestamp->value);
        }

        return $result;
    }
}