<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Contracts\Actions\CreateLogActionInterface;
use App\Modules\Logs\Contracts\Repositories\LogRepositoryInterface;
use App\Modules\Logs\Parameters\CreateLogParameters;

readonly class CreateLogAction implements CreateLogActionInterface
{
    public function __construct(
        protected LogRepositoryInterface $logRepository
    ) {
    }

    public function handle(CreateLogParameters $parameters): void
    {
        $this->logRepository->create($parameters);
    }
}
