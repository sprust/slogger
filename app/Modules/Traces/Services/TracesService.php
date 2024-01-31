<?php

namespace App\Modules\Traces\Services;

use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use App\Modules\Traces\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\Traces\Repository\TracesRepositoryInterface;

readonly class TracesService
{
    public function __construct(private TracesRepositoryInterface $tracesRepository)
    {
    }

    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $this->tracesRepository->createMany($parametersList);
    }

    public function updateMany(TraceUpdateParametersList $parametersList): void
    {
        $this->tracesRepository->updateMany($parametersList);
    }
}
