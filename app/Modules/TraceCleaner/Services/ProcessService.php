<?php

namespace App\Modules\TraceCleaner\Services;

use App\Modules\TraceCleaner\Repositories\Contracts\ProcessRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Dto\ProcessDto;
use App\Modules\TraceCleaner\Services\Objects\ProcessObject;

readonly class ProcessService
{
    public function __construct(
        private ProcessRepositoryInterface $processRepository
    ) {
    }

    /**
     * @return ProcessObject[]
     */
    public function find(int $page, ?int $settingId = null): array
    {
        $processes = $this->processRepository->find(
            page: $page,
            settingId: $settingId
        );

        return array_map(
            fn(ProcessDto $dto) => new ProcessObject(
                id: $dto->id,
                settingId: $dto->settingId,
                clearedCount: $dto->clearedCount,
                clearedAt: $dto->clearedAt,
                createdAt: $dto->createdAt,
                updatedAt: $dto->updatedAt,
            ),
            $processes
        );
    }
}
