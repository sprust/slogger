<?php

namespace App\Modules\TraceCleaner\Domain\Actions;

use App\Modules\TraceCleaner\Domain\Entities\Objects\ProcessObject;
use App\Modules\TraceCleaner\Domain\Entities\Transports\ProcessTransport;
use App\Modules\TraceCleaner\Repositories\Dto\ProcessDto;
use App\Modules\TraceCleaner\Repositories\Interfaces\ProcessRepositoryInterface;

readonly class FindProcessesAction
{
    public function __construct(
        private ProcessRepositoryInterface $processRepository
    ) {
    }

    /**
     * @return ProcessObject[]
     */
    public function handle(int $page, ?int $settingId = null): array
    {
        return array_map(
            fn(ProcessDto $dto) => ProcessTransport::toObject($dto),
            $this->processRepository->find(
                page: $page,
                settingId: $settingId
            )
        );
    }
}
