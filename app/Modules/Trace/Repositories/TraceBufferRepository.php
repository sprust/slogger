<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Contracts\Repositories\TraceBufferRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampMetricObject;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBufferDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBufferInvalidDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceBuffersDto;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use stdClass;
use Throwable;

readonly class TraceBufferRepository implements TraceBufferRepositoryInterface
{
    public function __construct(
        private Collection $collection
    ) {
    }

    public function create(TraceCreateParameters $trace): void
    {
        $timestamp = new UTCDateTime(now());

        $filter = [
            'sid' => $trace->serviceId,
            'tid' => $trace->traceId,
        ];

        $existTrace = $this->collection->findOne([
            ...$filter,
            '__ins' => false,
        ]) ?? [];

        $hasExistsTrace = count($existTrace) > 0;

        $profiling = ($existTrace['hpr'] ?? false)
            ? [
                'hpr' => true,
                'pr'  => $existTrace['pr'],
            ]
            : [];

        $this->collection->updateOne(
            $filter,
            [
                '$set' => [
                    'ptid'  => $trace->parentTraceId,
                    'tp'    => $trace->type,
                    'st'    => ($existTrace['st'] ?? null) ?: $trace->status,
                    'tgs'   => ($existTrace['tgs'] ?? null) ?: $trace->tags,
                    'dt'    => ($existTrace['dt'] ?? null) ?: $trace->data,
                    'dur'   => is_null($existTrace['dur'] ?? null) ? $trace->duration : $existTrace['dur'],
                    'mem'   => is_null($existTrace['mem'] ?? null) ? $trace->memory : $existTrace['mem'],
                    'cpu'   => is_null($existTrace['cpu'] ?? null) ? $trace->cpu : $existTrace['cpu'],
                    'tss'   => $this->makeTimestampsData($trace->timestamps),
                    'lat'   => new UTCDateTime($trace->loggedAt),
                    'uat'   => $timestamp,
                    'cat'   => $timestamp,
                    ...$profiling,
                    '__ins' => true,
                    '__upd' => $hasExistsTrace || !$trace->isParent,
                ],
            ],
            [
                'upsert' => true,
            ]
        );
    }

    public function update(TraceUpdateParameters $trace): bool
    {
        $timestamp = new UTCDateTime(now());

        $filter = [
            'sid' => $trace->serviceId,
            'tid' => $trace->traceId,
        ];

        $existTrace = $this->collection->findOne($filter) ?? [];

        $hasExistsTrace = count($existTrace) > 0;

        $profilingItems = $trace->profiling?->getItems() ?? [];

        $profiling = count($profilingItems)
            ? [
                'hpr' => true,
                'pr'  => [
                    'mainCaller' => $trace->profiling->getMainCaller(),
                    'items'      => $profilingItems,
                ],
            ]
            : [];

        if (!$hasExistsTrace) {
            $this->collection->insertOne([
                ...$filter,
                'ptid'  => null,
                'tp'    => null,
                'st'    => $trace->status,
                'tgs'   => $trace->tags ?: [],
                'dt'    => $trace->data ?: new stdClass(),
                'dur'   => $trace->duration,
                'mem'   => $trace->memory,
                'cpu'   => $trace->cpu,
                'tss'   => new stdClass(),
                'lat'   => $timestamp,
                'uat'   => $timestamp,
                'cat'   => $timestamp,
                ...$profiling,
                '__ins' => false,
                '__upd' => true,
            ]);

            return true;
        }

        $result = $this->collection->updateOne(
            $filter,
            [
                '$set' => [
                    'st'    => $trace->status,
                    ...(is_null($trace->tags)
                        ? []
                        : [
                            'tgs' => $trace->tags,
                        ]),
                    ...(is_null($trace->data)
                        ? []
                        : [
                            'dt' => $trace->data,
                        ]),
                    ...(is_null($trace->duration)
                        ? []
                        : [
                            'dur' => $trace->duration,
                        ]),
                    ...(is_null($trace->memory)
                        ? []
                        : [
                            'mem' => $trace->memory,
                        ]),
                    ...(is_null($trace->cpu)
                        ? []
                        : [
                            'cpu' => $trace->cpu,
                        ]),
                    'uat'   => $timestamp,
                    ...$profiling,
                    '__ins' => true,
                    '__upd' => true,
                ],
            ]
        );

        return $result->getModifiedCount() > 0;
    }

    public function findForHandling(int $page, int $perPage, Carbon $deadTimeLine): TraceBuffersDto
    {
        $pipeline = [
            [
                '$match' => [
                    '$or' => [
                        [
                            '__ins' => true,
                            '__upd' => true,
                        ],
                        [
                            'lat' => [
                                '$lte' => new UTCDateTime($deadTimeLine),
                            ],
                        ],
                    ],
                ],
            ],
            [
                '$sort' => [
                    'lat' => 1,
                ],
            ],
            [
                '$skip' => ($page - 1) * $perPage,
            ],
            [
                '$limit' => $perPage,
            ],
        ];

        $cursor = $this->collection->aggregate($pipeline);

        /** @var TraceBufferDto[] $result */
        $result = [];

        /** @var TraceBufferInvalidDto[] $invalidTraces */
        $invalidTraces = [];

        foreach ($cursor as $document) {
            try {
                $result[] = new TraceBufferDto(
                    id: (string) $document['_id'],
                    serviceId: $document['sid'],
                    traceId: $document['tid'],
                    parentTraceId: $document['ptid'],
                    type: $document['tp'] ?? 'unknown',
                    status: $document['st'],
                    tags: $document['tgs'],
                    data: $document['dt'],
                    duration: $document['dur'],
                    memory: $document['mem'],
                    cpu: $document['cpu'],
                    hasProfiling: $document['hpr'] ?? false,
                    profiling: $document['pr'] ?? null,
                    timestamps: $document['tss'],
                    loggedAt: new Carbon($document['lat']->toDateTime()),
                    createdAt: new Carbon($document['cat']->toDateTime()),
                    updatedAt: new Carbon($document['uat']->toDateTime()),
                    inserted: $document['__ins'],
                    updated: $document['__upd'],
                );
            } catch (Throwable $exception) {
                $traceId = $document['tid'] ?? null;

                if (!is_string($traceId)) {
                    $traceId = null;
                }

                $invalidTraces[] = new TraceBufferInvalidDto(
                    traceId: $traceId,
                    document: $document,
                    error: $exception->getMessage() . PHP_EOL . $exception->getTraceAsString(),
                );
            }
        }

        return new TraceBuffersDto(
            traces: $result,
            invalidTraces: $invalidTraces
        );
    }

    public function delete(array $traceIds): int
    {
        $result = $this->collection->deleteMany([
            'tid' => [
                '$in' => $traceIds,
            ],
        ]);

        return $result->getDeletedCount();
    }

    /**
     * @param TraceTimestampMetricObject[] $timestamps
     *
     * @return array<string, UTCDateTime>
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
