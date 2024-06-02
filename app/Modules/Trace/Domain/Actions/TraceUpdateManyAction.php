<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\Trace\Repositories\Interfaces\CollectorTraceRepositoryInterface;

readonly class TraceUpdateManyAction
{
    public function __construct(
        private CollectorTraceRepositoryInterface $traceRepository
    ) {
    }

    public function handle(TraceUpdateParametersList $parametersList): int
    {
        return $this->traceRepository->updateMany($parametersList);
    }
}
