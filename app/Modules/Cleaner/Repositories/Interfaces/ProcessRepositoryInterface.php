<?php

namespace App\Modules\Cleaner\Repositories\Interfaces;

use App\Modules\Cleaner\Repositories\Dto\ProcessDto;
use Illuminate\Support\Carbon;

interface ProcessRepositoryInterface
{
    /**
     * @return ProcessDto[]
     */
    public function find(int $page, ?int $settingId = null): array;

    public function findFirstBySettingId(int $settingId, bool $clearedAtIsNull): ?ProcessDto;

    public function create(int $settingId, int $clearedCount, ?Carbon $clearedAt): ProcessDto;

    public function update(string $processId, int $clearedCount, ?Carbon $clearedAt): void;

    public function deleteByProcessId(string $processId): void;
}
