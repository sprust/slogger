<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Contracts\Repositories;

use App\Modules\Cleaner\Entities\ProcessObject;
use Illuminate\Support\Carbon;
use Throwable;

interface ProcessRepositoryInterface
{
    /**
     * @return ProcessObject[]
     */
    public function find(int $page, int $perPage): array;

    public function exists(bool $clearedAtIsNull): ?ProcessObject;

    public function create(): ProcessObject;

    public function update(
        string $processId,
        int $clearedCollectionsCount,
        int $clearedTracesCount,
        ?Carbon $clearedAt,
        ?Throwable $exception
    ): void;

    public function deleteByProcessId(string $processId): void;
}
