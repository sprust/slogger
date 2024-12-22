<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\FindProcessesActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;

readonly class FindProcessesAction implements FindProcessesActionInterface
{
    public function __construct(
        private ProcessRepositoryInterface $processRepository
    ) {
    }

    public function handle(int $page, ?int $settingId = null): array
    {
        return $this->processRepository->find(
            page: $page,
            settingId: $settingId
        );
    }
}
