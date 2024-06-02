<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Objects\ProcessObject;
use App\Modules\Trace\Domain\Entities\Transports\ProcessTransport;
use App\Modules\Trace\Repositories\Dto\ProcessDto;
use App\Modules\Trace\Repositories\Interfaces\ProcessRepositoryInterface;

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
