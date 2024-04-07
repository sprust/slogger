<?php

namespace App\Modules\TraceCollector\Domain\Actions;

use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;

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
