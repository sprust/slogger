<?php

namespace App\Modules\Cleaner\Contracts\Repositories;

use App\Modules\Cleaner\Entities\ProcessObject;
use Illuminate\Support\Carbon;
use Throwable;

interface ProcessRepositoryInterface
{
    /**
     * @return ProcessObject[]
     */
    public function find(int $page, ?int $settingId = null): array;

    public function findFirstBySettingId(int $settingId, bool $clearedAtIsNull): ?ProcessObject;

    public function create(int $settingId, int $clearedCount, ?Carbon $clearedAt): ProcessObject;

    public function update(
        string $processId,
        int $clearedCount,
        ?Carbon $clearedAt,
        ?Throwable $exception = null
    ): void;

    public function deleteByProcessId(string $processId): void;
}
