<?php

namespace App\Modules\TraceCollector\Services;

use App\Modules\TraceCollector\Adapters\TraceAggregatorTreeRepositoryAdapter;
use App\Modules\TraceCollector\Dto\Parameters\TraceCreateParameters;
use App\Modules\TraceCollector\Dto\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Dto\Parameters\TraceTreeCreateParameters;
use App\Modules\TraceCollector\Dto\Parameters\TraceUpdateParametersList;
use App\Modules\TraceCollector\Repository\TraceRepositoryInterface;

readonly class TraceService
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceAggregatorTreeRepositoryAdapter $treeRepositoryAdapter
    ) {
    }

    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $this->traceRepository->createMany($parametersList);

        $this->treeRepositoryAdapter->insertMany(
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
        return $this->traceRepository->updateMany($parametersList);
    }
}
