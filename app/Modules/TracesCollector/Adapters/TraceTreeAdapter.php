<?php

namespace App\Modules\TracesCollector\Adapters;

use App\Modules\TracesCollector\Dto\Parameters\TraceTreeCreateParameters;
use App\Modules\TracesAggregator\Dto\Parameters\TraceTreeInsertParameters;
use App\Modules\TracesAggregator\Repositories\TraceTreeRepositoryInterface;

readonly class TraceTreeAdapter
{
    public function __construct(private TraceTreeRepositoryInterface $traceTreeRepository)
    {
    }

    /**
     * @param TraceTreeCreateParameters[] $parametersList
     */
    public function insertMany(array $parametersList): void
    {
        $this->traceTreeRepository->insertMany(
            array_map(
                fn(TraceTreeCreateParameters $parameters) => new TraceTreeInsertParameters(
                    traceId: $parameters->traceId,
                    parentTraceId: $parameters->parentTraceId,
                    loggedAt: $parameters->loggedAt
                ),
                $parametersList
            )
        );
    }
}
