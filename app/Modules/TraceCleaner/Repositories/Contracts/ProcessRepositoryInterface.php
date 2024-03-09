<?php

namespace App\Modules\TraceCleaner\Repositories\Contracts;

use App\Modules\TraceCleaner\Repositories\Dto\ProcessDto;
use Illuminate\Support\Carbon;

interface ProcessRepositoryInterface
{
    public function findFirstBySettingId(int $settingId, bool $clearedAtIsNotNull): ?ProcessDto;

    public function create(int $settingId, int $clearedCount, ?Carbon $clearedAt): ProcessDto;

    public function update(int $processId, int $clearedCount, ?Carbon $clearedAt): void;
}
