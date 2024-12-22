<?php

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Parameters\CreateLogParameters;
use App\Modules\Logs\Repositories\LogRepository;

readonly class CreateLogAction
{
    public function __construct(
        protected LogRepository $logRepository
    ) {
    }

    public function handle(CreateLogParameters $parameters): void
    {
        $this->logRepository->create($parameters);
    }
}
