<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\Actions;

use App\Modules\Cleaner\Entities\ProcessObject;
use App\Modules\Cleaner\Repositories\ProcessRepository;

readonly class FindProcessesAction
{
    private int $perPage;

    public function __construct(
        private ProcessRepository $processRepository
    ) {
        $this->perPage = 30;
    }

    /**
     * @return ProcessObject[]
     */
    public function handle(int $page): array
    {
        return $this->processRepository->find(
            page: $page,
            perPage: $this->perPage,
        );
    }
}
