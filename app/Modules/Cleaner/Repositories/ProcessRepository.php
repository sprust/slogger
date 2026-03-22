<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Repositories;

use App\Models\Traces\TraceClearingProcess;
use App\Modules\Cleaner\Entities\ProcessObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use Throwable;

class ProcessRepository
{
    /**
     * @return ProcessObject[]
     */
    public function find(int $page, int $perPage): array
    {
        return TraceClearingProcess::query()
            ->orderByDesc('createdAt')
            ->forPage(
                page: $page,
                perPage: $perPage
            )
            ->get()
            ->map(fn(TraceClearingProcess $process) => new ProcessObject(
                id: $process->_id,
                clearedCollectionsCount: $process->clearedCollectionsCount,
                clearedTracesCount: $process->clearedTracesCount,
                error: $process->error,
                clearedAt: $process->clearedAt,
                createdAt: $process->createdAt,
                updatedAt: $process->updatedAt
            ))
            ->toArray();
    }

    public function exists(bool $clearedAtIsNull): ?ProcessObject
    {
        /** @var TraceClearingProcess|null $process */
        $process = TraceClearingProcess::query()
            ->when(
                value: $clearedAtIsNull,
                callback: fn(Builder $query) => $query->whereNull('clearedAt'),
                default: fn(Builder $query) => $query->whereNotNull('clearedAt')
            )
            ->orderByDesc('createdAt')
            ->first();

        if (!$process) {
            return null;
        }

        return new ProcessObject(
            id: $process->_id,
            clearedCollectionsCount: $process->clearedCollectionsCount,
            clearedTracesCount: $process->clearedTracesCount,
            error: $process->error,
            clearedAt: $process->clearedAt,
            createdAt: $process->createdAt,
            updatedAt: $process->updatedAt
        );
    }

    public function create(): ProcessObject
    {
        $newProgress = new TraceClearingProcess();

        $newProgress->clearedCollectionsCount = 0;
        $newProgress->clearedTracesCount = 0;
        $newProgress->error = null;
        $newProgress->errorTrace = null;
        $newProgress->clearedAt = null;

        $newProgress->save();

        return new ProcessObject(
            id: $newProgress->_id,
            clearedCollectionsCount: $newProgress->clearedCollectionsCount,
            clearedTracesCount: $newProgress->clearedTracesCount,
            error: $newProgress->error,
            clearedAt: $newProgress->clearedAt,
            createdAt: $newProgress->createdAt,
            updatedAt: $newProgress->updatedAt
        );
    }

    public function update(
        string $processId,
        int $clearedCollectionsCount,
        int $clearedTracesCount,
        ?Carbon $clearedAt,
        ?Throwable $exception
    ): void {
        TraceClearingProcess::query()
            ->where('_id', $processId)
            ->update([
                'clearedCollectionsCount' => $clearedCollectionsCount,
                'clearedTracesCount' => $clearedTracesCount,
                'error'        => $exception ? ($exception->getMessage() ?: $exception::class) : null,
                'errorTrace'   => $exception ? ($exception->getTraceAsString()) : null,
                'clearedAt'    => $clearedAt ? new UTCDateTime($clearedAt) : null,
            ]);
    }

    public function deleteByProcessId(string $processId): void
    {
        TraceClearingProcess::query()
            ->where('_id', $processId)
            ->delete();
    }
}
