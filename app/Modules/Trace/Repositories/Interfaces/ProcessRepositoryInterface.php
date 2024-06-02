<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\ProcessDto;
use Illuminate\Support\Carbon;

interface ProcessRepositoryInterface
{
    /**
     * @return ProcessDto[]
     */
    public function find(int $page, ?int $settingId = null): array;

    public function findFirstBySettingId(int $settingId, bool $clearedAtIsNotNull): ?ProcessDto;

    public function create(int $settingId, int $clearedCount, ?Carbon $clearedAt): ProcessDto;

    public function update(int $processId, int $clearedCount, ?Carbon $clearedAt): void;

    public function delete(Carbon $to): void;
}