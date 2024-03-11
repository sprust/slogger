<?php

namespace App\Modules\TraceCleaner\Repositories;

use App\Models\Traces\TraceClearingProcess;
use App\Modules\TraceCleaner\Repositories\Contracts\ProcessRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Dto\ProcessDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ProcessRepository implements ProcessRepositoryInterface
{
    private int $perPage = 20;

    public function find(int $page, ?int $settingId = null): array
    {
        return TraceClearingProcess::query()
            ->when($settingId, fn(Builder $query) => $query->where('setting_id', $settingId))
            ->orderByDesc('created_at')
            ->forPage(
                page: $page,
                perPage: $this->perPage
            )
            ->get()
            ->map(fn(TraceClearingProcess $process) => new ProcessDto(
                id: $process->id,
                settingId: $process->setting_id,
                clearedCount: $process->cleared_count,
                clearedAt: $process->cleared_at,
                createdAt: $process->created_at,
                updatedAt: $process->updated_at
            ))
            ->toArray();
    }

    public function findFirstBySettingId(int $settingId, bool $clearedAtIsNotNull): ?ProcessDto
    {
        /** @var TraceClearingProcess|null $process */
        $process = TraceClearingProcess::query()
            ->where('setting_id')
            ->when(
                value: $clearedAtIsNotNull,
                callback: fn(Builder $query) => $query->whereNotNull('cleared_at'),
                default: fn(Builder $query) => $query->whereNull('cleared_at')
            )
            ->orderByDesc('created_at')
            ->first();

        if (!$process) {
            return null;
        }

        return new ProcessDto(
            id: $process->id,
            settingId: $process->setting_id,
            clearedCount: $process->cleared_count,
            clearedAt: $process->cleared_at,
            createdAt: $process->created_at,
            updatedAt: $process->updated_at
        );
    }

    public function create(int $settingId, int $clearedCount, ?Carbon $clearedAt): ProcessDto
    {
        $newProgress = new TraceClearingProcess();

        $newProgress->setting_id = $settingId;
        $newProgress->cleared_count = $clearedCount;
        $newProgress->cleared_at = $clearedAt;

        $newProgress->saveOrFail();

        return new ProcessDto(
            id: $newProgress->id,
            settingId: $newProgress->setting_id,
            clearedCount: $newProgress->cleared_count,
            clearedAt: $newProgress->cleared_at,
            createdAt: $newProgress->created_at,
            updatedAt: $newProgress->updated_at
        );
    }

    public function update(int $processId, int $clearedCount, ?Carbon $clearedAt): void
    {
        TraceClearingProcess::query()
            ->where('id', $processId)
            ->update([
                'cleared_count' => $clearedCount,
                'cleared_at'    => $clearedAt,
            ]);
    }

    public function delete(Carbon $to): void
    {
        TraceClearingProcess::query()->where('created_at', '<=', $to)->delete();
    }
}
