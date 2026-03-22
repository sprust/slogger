<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Contracts\Actions\FindProcessesActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;

readonly class FindProcessesAction implements FindProcessesActionInterface
{
    private int $perPage;

    public function __construct(
        private ProcessRepositoryInterface $processRepository
    ) {
        $this->perPage = 20;
    }

    public function handle(int $page): array
    {
        return $this->processRepository->find(
            page: $page,
            perPage: $this->perPage,
        );
    }
}
