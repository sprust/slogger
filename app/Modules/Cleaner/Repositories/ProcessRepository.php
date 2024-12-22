<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Repositories;

use App\Models\Traces\TraceClearingProcess;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;
use App\Modules\Cleaner\Entities\ProcessObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\BSON\UTCDateTime;
use Throwable;

class ProcessRepository implements ProcessRepositoryInterface
{
    private int $perPage = 20;

    public function find(int $page, ?int $settingId = null): array
    {
        return TraceClearingProcess::query()
            ->when($settingId, fn(Builder $query) => $query->where('settingId', $settingId))
            ->orderByDesc('createdAt')
            ->forPage(
                page: $page,
                perPage: $this->perPage
            )
            ->get()
            ->map(fn(TraceClearingProcess $process) => new ProcessObject(
                id: $process->_id,
                settingId: $process->settingId,
                clearedCount: $process->clearedCount,
                error: $process->error,
                clearedAt: $process->clearedAt,
                createdAt: $process->createdAt,
                updatedAt: $process->updatedAt
            ))
            ->toArray();
    }

    public function findFirstBySettingId(int $settingId, bool $clearedAtIsNull): ?ProcessObject
    {
        /** @var TraceClearingProcess|null $process */
        $process = TraceClearingProcess::query()
            ->where('settingId', $settingId)
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
            settingId: $process->settingId,
            clearedCount: $process->clearedCount,
            error: $process->error,
            clearedAt: $process->clearedAt,
            createdAt: $process->createdAt,
            updatedAt: $process->updatedAt
        );
    }

    public function create(int $settingId, int $clearedCount, ?Carbon $clearedAt): ProcessObject
    {
        $newProgress = new TraceClearingProcess();

        $newProgress->settingId    = $settingId;
        $newProgress->clearedCount = $clearedCount;
        $newProgress->error        = null;
        $newProgress->clearedAt    = $clearedAt;

        $newProgress->save();

        return new ProcessObject(
            id: $newProgress->_id,
            settingId: $newProgress->settingId,
            clearedCount: $newProgress->clearedCount,
            error: $newProgress->error,
            clearedAt: $newProgress->clearedAt,
            createdAt: $newProgress->createdAt,
            updatedAt: $newProgress->updatedAt
        );
    }

    public function update(
        string $processId,
        int $clearedCount,
        ?Carbon $clearedAt,
        ?Throwable $exception = null
    ): void {
        TraceClearingProcess::query()
            ->where('_id', $processId)
            ->update([
                'clearedCount' => $clearedCount,
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
