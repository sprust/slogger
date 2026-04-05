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
        return TraceTreeCacheState::query()
            ->when(
                $excludeStatus !== null,
                static fn($query) => $query->where('status', '!=', $excludeStatus?->value)
            )
            ->orderByDesc('updatedAt')
            ->take($limit)
            ->get()
            ->map(fn(TraceTreeCacheState $state) => $this->modelToObject($state))
            ->all();
    }

    public function findOneByRootTraceId(string $rootTraceId): ?TraceTreeCacheStateObject
    {
        /** @var TraceTreeCacheState|null $state */
        $state = TraceTreeCacheState::query()
            ->where('rootTraceId', $rootTraceId)
            ->first();

        return $state ? $this->modelToObject($state) : null;
    }

    public function reset(
        string $rootTraceId,
        string $version,
        TraceTreeCacheStateStatusEnum $status,
    ): TraceTreeCacheStateObject {
        $startedAt = now();

        TraceTreeCacheState::query()
            ->updateOrInsert(
                [
                    'rootTraceId' => $rootTraceId,
                ],
                [
                    'version'    => $version,
                    'status'     => $status,
                    'count'      => 0,
                    'error'      => null,
                    'startedAt'  => new UTCDateTime($startedAt),
                    'finishedAt' => null,
                    'updatedAt'  => new UTCDateTime($startedAt),
                    'createdAt'  => new UTCDateTime($startedAt),
                ]
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

        TraceTreeCacheState::query()
            ->updateOrInsert(
                [
                    'rootTraceId' => $rootTraceId,
                ],
                [
                    'version'    => $version,
                    'status'     => TraceTreeCacheStateStatusEnum::Finished->value,
                    'count'      => $count,
                    'error'      => null,
                    'startedAt'  => new UTCDateTime($finishedAt),
                    'finishedAt' => new UTCDateTime($finishedAt),
                    'updatedAt'  => new UTCDateTime($finishedAt),
                    'createdAt'  => new UTCDateTime($finishedAt),
                ]
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
        return (bool) TraceTreeCacheState::query()
            ->where('rootTraceId', $rootTraceId)
            ->update([
                'status'     => $status->value,
                'error'      => $error,
                'finishedAt' => $finishedAt ? new UTCDateTime($finishedAt) : null,
                'updatedAt'  => new UTCDateTime(now()),
            ]);
    }

    public function deleteByRootTraceId(string $rootTraceId): bool
    {
        return (bool) TraceTreeCacheState::query()
            ->where('rootTraceId', $rootTraceId)
            ->delete();
    }

    public function markFinished(string $rootTraceId, string $version): bool
    {
        $finishedAt = now();

        return (bool) TraceTreeCacheState::query()
            ->where('rootTraceId', $rootTraceId)
            ->where('version', $version)
            ->where('status', TraceTreeCacheStateStatusEnum::InProcess->value)
            ->update([
                'status'     => TraceTreeCacheStateStatusEnum::Finished->value,
                'error'      => null,
                'finishedAt' => new UTCDateTime($finishedAt),
                'updatedAt'  => new UTCDateTime($finishedAt),
            ]);
    }

    public function incrementCount(string $rootTraceId, string $version, int $count): bool
    {
        if ($count <= 0) {
            return false;
        }

        return (bool) TraceTreeCacheState::query()
            ->where('rootTraceId', $rootTraceId)
            ->where('version', $version)
            ->where('status', TraceTreeCacheStateStatusEnum::InProcess->value)
            ->increment(
                column: 'count',
                amount: $count,
                extra: [
                    'updatedAt' => new UTCDateTime(now()),
                ]
            );
    }

    public function markFailed(string $rootTraceId, string $version, string $error): bool
    {
        $finishedAt = now();

        return (bool) TraceTreeCacheState::query()
            ->where('rootTraceId', $rootTraceId)
            ->where('version', $version)
            ->update([
                'status'     => TraceTreeCacheStateStatusEnum::Failed->value,
                'error'      => $error,
                'finishedAt' => new UTCDateTime($finishedAt),
                'updatedAt'  => new UTCDateTime($finishedAt),
            ]);
    }

    private function modelToObject(TraceTreeCacheState $state): TraceTreeCacheStateObject
    {
        return new TraceTreeCacheStateObject(
            rootTraceId: $state->rootTraceId,
            version: $state->version,
            status: $state->status,
            count: $state->count,
            error: $state->error,
            startedAt: $state->startedAt,
            finishedAt: $state->finishedAt,
            createdAt: $state->createdAt,
            updatedAt: $state->updatedAt,
        );
    }
}
