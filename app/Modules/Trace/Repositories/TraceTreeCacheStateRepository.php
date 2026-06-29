<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTreeCacheState;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use RuntimeException;

class TraceTreeCacheStateRepository
{
    /**
     * @return TraceTreeCacheStateObject[]
     */
    public function findMany(
        int $limit,
        ?TraceTreeCacheStateStatusEnum $excludeStatus = null,
    ): array {
        $filter = [];

        if ($excludeStatus !== null) {
            $filter['status'] = ['$ne' => $excludeStatus->value];
        }

        $cursor = TraceTreeCacheState::sconcur()->find(
            filter: $filter,
            sort: ['updatedAt' => -1],
            limit: $limit,
        );

        $states = [];

        foreach ($cursor as $document) {
            $states[] = $this->documentToObject($document);
        }

        return $states;
    }

    public function findOneByRootTraceId(string $rootTraceId): ?TraceTreeCacheStateObject
    {
        $document = TraceTreeCacheState::sconcur()->findOne(['rootTraceId' => $rootTraceId]);

        return $document ? $this->documentToObject($document) : null;
    }

    public function reset(
        string $rootTraceId,
        string $version,
        TraceTreeCacheStateStatusEnum $status,
    ): TraceTreeCacheStateObject {
        $startedAt = now();

        TraceTreeCacheState::sconcur()->updateOne(
            filter: ['rootTraceId' => $rootTraceId],
            update: [
                '$set' => [
                    'version'    => $version,
                    'status'     => $status->value,
                    'count'      => 0,
                    'error'      => null,
                    'startedAt'  => new UTCDateTime($startedAt),
                    'finishedAt' => null,
                    'updatedAt'  => new UTCDateTime($startedAt),
                    'createdAt'  => new UTCDateTime($startedAt),
                ],
            ],
            upsert: true,
        );

        $state = $this->findOneByRootTraceId($rootTraceId);

        if ($state === null) {
            throw new RuntimeException('Trace tree cache state was not created.');
        }

        return $state;
    }

    public function saveFinished(string $rootTraceId, string $version, int $count = 0): TraceTreeCacheStateObject
    {
        $finishedAt = now();

        TraceTreeCacheState::sconcur()->updateOne(
            filter: ['rootTraceId' => $rootTraceId],
            update: [
                '$set' => [
                    'version'    => $version,
                    'status'     => TraceTreeCacheStateStatusEnum::Finished->value,
                    'count'      => $count,
                    'error'      => null,
                    'startedAt'  => new UTCDateTime($finishedAt),
                    'finishedAt' => new UTCDateTime($finishedAt),
                    'updatedAt'  => new UTCDateTime($finishedAt),
                    'createdAt'  => new UTCDateTime($finishedAt),
                ],
            ],
            upsert: true,
        );

        $state = $this->findOneByRootTraceId($rootTraceId);

        if ($state === null) {
            throw new RuntimeException('Trace tree cache state was not saved.');
        }

        return $state;
    }

    public function updateStatus(
        string $rootTraceId,
        TraceTreeCacheStateStatusEnum $status,
        ?Carbon $finishedAt = null,
        ?string $error = null,
    ): bool {
        return TraceTreeCacheState::sconcur()
            ->updateMany(
                filter: ['rootTraceId' => $rootTraceId],
                update: [
                    '$set' => [
                        'status'     => $status->value,
                        'error'      => $error,
                        'finishedAt' => $finishedAt ? new UTCDateTime($finishedAt) : null,
                        'updatedAt'  => new UTCDateTime(now()),
                    ],
                ],
            )
            ->matchedCount > 0;
    }

    public function deleteByRootTraceId(string $rootTraceId): bool
    {
        return TraceTreeCacheState::sconcur()
            ->deleteMany(['rootTraceId' => $rootTraceId])
            ->deletedCount > 0;
    }

    public function markFinished(string $rootTraceId, string $version): bool
    {
        $finishedAt = now();

        return TraceTreeCacheState::sconcur()
            ->updateMany(
                filter: [
                    'rootTraceId' => $rootTraceId,
                    'version'     => $version,
                    'status'      => TraceTreeCacheStateStatusEnum::InProcess->value,
                ],
                update: [
                    '$set' => [
                        'status'     => TraceTreeCacheStateStatusEnum::Finished->value,
                        'error'      => null,
                        'finishedAt' => new UTCDateTime($finishedAt),
                        'updatedAt'  => new UTCDateTime($finishedAt),
                    ],
                ],
            )
            ->matchedCount > 0;
    }

    public function incrementCount(string $rootTraceId, string $version, int $count): bool
    {
        if ($count <= 0) {
            return false;
        }

        return TraceTreeCacheState::sconcur()
            ->updateMany(
                filter: [
                    'rootTraceId' => $rootTraceId,
                    'version'     => $version,
                    'status'      => TraceTreeCacheStateStatusEnum::InProcess->value,
                ],
                update: [
                    '$inc' => [
                        'count' => $count,
                    ],
                    '$set' => [
                        'updatedAt' => new UTCDateTime(now()),
                    ],
                ],
            )
            ->matchedCount > 0;
    }

    public function markFailed(string $rootTraceId, string $version, string $error): bool
    {
        $finishedAt = now();

        return TraceTreeCacheState::sconcur()
            ->updateMany(
                filter: [
                    'rootTraceId' => $rootTraceId,
                    'version'     => $version,
                ],
                update: [
                    '$set' => [
                        'status'     => TraceTreeCacheStateStatusEnum::Failed->value,
                        'error'      => $error,
                        'finishedAt' => new UTCDateTime($finishedAt),
                        'updatedAt'  => new UTCDateTime($finishedAt),
                    ],
                ],
            )
            ->matchedCount > 0;
    }

    /**
     * @param array<int|string, mixed> $document
     */
    private function documentToObject(array $document): TraceTreeCacheStateObject
    {
        return new TraceTreeCacheStateObject(
            rootTraceId: $document['rootTraceId'],
            version: $document['version'],
            status: TraceTreeCacheStateStatusEnum::from($document['status']),
            count: $document['count'],
            error: $document['error'] ?? null,
            startedAt: isset($document['startedAt'])
                ? new Carbon($document['startedAt']->toDateTime())
                : null,
            finishedAt: isset($document['finishedAt'])
                ? new Carbon($document['finishedAt']->toDateTime())
                : null,
            createdAt: new Carbon($document['createdAt']->toDateTime()),
            updatedAt: new Carbon($document['updatedAt']->toDateTime()),
        );
    }
}
