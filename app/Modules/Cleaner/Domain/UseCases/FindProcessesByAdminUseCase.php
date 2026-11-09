<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Domain\UseCases;

use App\Modules\Cleaner\Entities\ProcessObject;
use App\Modules\Cleaner\Domain\Actions\FindProcessesAction;

readonly class FindProcessesByAdminUseCase
{
    public function __construct(private FindProcessesAction $findProcessesAction)
    {
    }

    /**
     * @return ProcessObject[]
     */
    public function handle(int $page): array
    {
        return $this->findProcessesAction->handle(
            page: $page,
        );
    }
}
