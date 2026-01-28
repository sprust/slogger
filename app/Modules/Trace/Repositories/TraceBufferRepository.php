<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Contracts\Repositories\TraceBufferRepositoryInterface;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampMetricObject;
use App\Modules\Trace\Parameters\TraceCreateParameters;
use App\Modules\Trace\Parameters\TraceUpdateParameters;
use App\Modules\Trace\Repositories\Dto\Buffer\CreatingTraceBufferDto;
use App\Modules\Trace\Repositories\Dto\Buffer\TraceBufferDto;
use App\Modules\Trace\Repositories\Dto\Buffer\TraceBufferInvalidDto;
use App\Modules\Trace\Repositories\Dto\Buffer\TraceBuffersDto;
use App\Modules\Trace\Repositories\Dto\Buffer\UpdatingTraceBufferDto;
use Illuminate\Support\Carbon;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use stdClass;
use Throwable;

readonly class TraceBufferRepository implements TraceBufferRepositoryInterface
{
    public function __construct(
        private Collection $collection,
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
                    'ptid'   => $trace->parentTraceId,
                    'tp'     => $trace->type,
                    'st'     => ($existTrace['st'] ?? null) ?: $trace->status,
                    'tgs'    => ($existTrace['tgs'] ?? null) ?: $trace->tags,
                    'dt'     => ($existTrace['dt'] ?? null) ?: $trace->data,
                    'dur'    => is_null($existTrace['dur'] ?? null) ? $trace->duration : $existTrace['dur'],
                    'mem'    => is_null($existTrace['mem'] ?? null) ? $trace->memory : $existTrace['mem'],
                    'cpu'    => is_null($existTrace['cpu'] ?? null) ? $trace->cpu : $existTrace['cpu'],
                    'tss'    => $this->makeTimestampsData($trace->timestamps),
                    'lat'    => new UTCDateTime($trace->loggedAt),
                    'uat'    => $timestamp,
                    'cat'    => $timestamp,
                    ...$profiling,
                    '__ins'  => true,
                    '__upd'  => $hasExistsTrace || !$trace->isParent,
                    '__hand' => false,
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

        $existTrace = $this->collection->findOne($filter) ?: [];

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
                'ptid'   => null,
                'tp'     => null,
                'st'     => $trace->status,
                'tgs'    => $trace->tags ?: [],
                'dt'     => $trace->data ?: '{}',
                'dur'    => $trace->duration,
                'mem'    => $trace->memory,
                'cpu'    => $trace->cpu,
                'tss'    => new stdClass(),
                'lat'    => $timestamp,
                'uat'    => $timestamp,
                'cat'    => $timestamp,
                ...$profiling,
                '__ins'  => false,
                '__upd'  => true,
                '__hand' => false,
            ]);

            return true;
        }

        $result = $this->collection->updateOne(
            filter: $filter,
            update: [
                '$set' => [
                    'st'     => $trace->status,
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
                    'uat'    => $timestamp,
                    ...$profiling,
                    '__ins'  => true,
                    '__upd'  => true,
                    '__hand' => false,
                ],
            ]
        );

        return $result->getModifiedCount() > 0;
    }

    public function findForHandling(int $page, int $perPage): TraceBuffersDto
    {
        $pipeline = [
            [
                '$match' => [
                    '$or' => [
                        [
                            'op' => [
                                '$in' => ['c', 'u'],
                            ],
                        ],
                        [
                            '__ins' => true,
                            '__upd' => true,
                        ],
                        [
                            '__hand' => false,
                        ],
                    ],
                ],
            ],
            [
                '$sort' => [
                    'cat' => 1,
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
        /** @var CreatingTraceBufferDto[] $creatingTraces */
        $creatingTraces = [];
        /** @var UpdatingTraceBufferDto[] $updatingTraces */
        $updatingTraces = [];
        /** @var TraceBufferInvalidDto[] $invalidTraces */
        $invalidTraces = [];

        foreach ($cursor as $document) {
            try {
                $traceBuffer = $this->documentToTraceBuffers($document);

                if ($traceBuffer !== null) {
                    $result[] = $traceBuffer;

                    continue;
                }

                $creatingTrace = $this->documentToCreating($document);

                if ($creatingTrace !== null) {
                    $creatingTraces[] = $creatingTrace;

                    continue;
                }

                $updatingTrace = $this->documentToUpdating($document);

                if ($updatingTrace !== null) {
                    $updatingTraces[] = $updatingTrace;

                    continue;
                }

                $invalidTraces[] = new TraceBufferInvalidDto(
                    id: (string) $document['_id'],
                    traceId: $document['tid'] ?? null,
                    document: $document,
                    error: 'Unknown document type',
                );
            } catch (Throwable $exception) {
                $traceId = $document['tid'] ?? null;

                if (!is_string($traceId)) {
                    $traceId = null;
                }

                $invalidTraces[] = new TraceBufferInvalidDto(
                    id: (string) $document['_id'],
                    traceId: $traceId,
                    document: $document,
                    error: $exception->getMessage() . PHP_EOL . $exception->getTraceAsString(),
                );
            }
        }

        return new TraceBuffersDto(
            traces: $result,
            creatingTraces: $creatingTraces,
            updatingTraces: $updatingTraces,
            invalidTraces: $invalidTraces
        );
    }

    public function markAsHandled(array $ids): void
    {
        if (count($ids) === 0) {
            return;
        }

        $objectIds = array_map(
            static fn(string $id) => new ObjectId($id),
            $ids
        );

        $this->collection->updateMany(
            [
                '_id'    => [
                    '$in' => $objectIds,
                ],
                '__hand' => false,
            ],
            [
                '$set' => [
                    '__hand' => true,
                ],
            ]
        );
    }

    public function delete(array $ids): int
    {
        if (count($ids) === 0) {
            return 0;
        }

        $objectIds = array_map(
            static fn(string $id) => new ObjectId($id),
            $ids
        );

        $result = $this->collection->deleteMany([
            '_id' => [
                '$in' => $objectIds,
            ],
        ]);

        return $result->getDeletedCount();
    }

    /**
     * @param array<string, mixed> $document
     */
    private function documentToTraceBuffers(array $document): ?TraceBufferDto
    {
        if (
            !array_key_exists('__ins', $document)
            && !array_key_exists('__upd', $document)
            && !array_key_exists('__hand', $document)
        ) {
            return null;
        }

        $loggedAt = $document['lat'] ?? new UTCDateTime();

        $dt = $document['dt'] ?? '{}';

        if (is_array($dt)) {
            $dt = json_encode($dt);
        }

        return new TraceBufferDto(
            id: (string) $document['_id'],
            serviceId: $document['sid'],
            traceId: $document['tid'],
            parentTraceId: $document['ptid'] ?? null,
            type: $document['tp'] ?? 'unknown',
            status: $document['st'],
            tags: $document['tgs'] ?? [],
            data: $dt,
            duration: $document['dur'] ?? null,
            memory: $document['mem'] ?? null,
            cpu: $document['cpu'] ?? null,
            hasProfiling: $document['hpr'] ?? false,
            profiling: $document['pr'] ?? null,
            timestamps: $document['tss'] ?? [],
            loggedAt: new Carbon($loggedAt->toDateTime()),
            createdAt: new Carbon($document['cat']->toDateTime()),
            updatedAt: new Carbon($document['uat']->toDateTime()),
            inserted: $document['__ins'],
            updated: $document['__upd'],
            handled: $document['__hand'] ?? false,
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    private function documentToCreating(array $document): ?CreatingTraceBufferDto
    {
        if (($document['op'] ?? null) !== 'c') {
            return null;
        }

        /** @var UTCDateTime $loggedAt */
        $loggedAt = $document['lat'];

        return new CreatingTraceBufferDto(
            id: (string) $document['_id'],
            serviceId: (int) $document['sid'],
            traceId: $document['tid'],
            parentTraceId: $document['ptid'] ?? null,
            type: $document['tp'],
            status: $document['st'],
            tags: $document['tgs'],
            data: $document['dt'],
            duration: $document['dur'] ?? null,
            memory: $document['mem'] ?? null,
            cpu: $document['cpu'] ?? null,
            loggedAt: new Carbon($loggedAt->toDateTime()),
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    private function documentToUpdating(array $document): ?UpdatingTraceBufferDto
    {
        if (($document['op'] ?? null) !== 'u') {
            return null;
        }

        /** @var UTCDateTime $loggedAt */
        $loggedAt = $document['plat'];

        return new UpdatingTraceBufferDto(
            id: (string) $document['_id'],
            serviceId: (int) $document['sid'],
            traceId: $document['tid'],
            status: $document['st'],
            tags: $document['tgs'] ?? null,
            data: $document['dt'],
            duration: $document['dur'] ?? null,
            memory: $document['mem'] ?? null,
            cpu: $document['cpu'] ?? null,
            parentLoggedAt: new Carbon($loggedAt->toDateTime()),
        );
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
