<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;

readonly class TraceUpdateManyAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceUpdateParametersList $parametersList): int
    {
        return $this->traceRepository->updateMany($parametersList);
    }
}
