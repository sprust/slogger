<?php

namespace App\Modules\TracesCollector\Services;

use App\Modules\TracesCollector\Adapters\TraceTreeAdapter;
use App\Modules\TracesCollector\Dto\Parameters\TraceCreateParameters;
use App\Modules\TracesCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TracesCollector\Dto\Parameters\TraceTreeCreateParameters;
use App\Modules\TracesCollector\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\TracesCollector\Repository\TracesRepositoryInterface;

readonly class TracesService
{
    public function __construct(
        private TracesRepositoryInterface $tracesRepository,
        private TraceTreeAdapter $traceTreeAdapter
    ) {
    }

    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $this->tracesRepository->createMany($parametersList);

        $this->traceTreeAdapter->insertMany(
            array_map(
                fn(TraceCreateParameters $traceCreateParameters) => new TraceTreeCreateParameters(
                    traceId: $traceCreateParameters->traceId,
                    parentTraceId: $traceCreateParameters->parentTraceId,
                    loggedAt: $traceCreateParameters->loggedAt
                ),
                $parametersList->getItems()
            )
        );
    }

    public function updateMany(TraceUpdateParametersList $parametersList): int
    {
        return $this->tracesRepository->updateMany($parametersList);
    }
}
