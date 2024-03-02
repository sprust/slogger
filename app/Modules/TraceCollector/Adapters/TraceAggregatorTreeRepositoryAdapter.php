<?php

namespace App\Modules\TraceCollector\Adapters;

use App\Modules\TraceCollector\Dto\Parameters\TraceTreeCreateParameters;
use App\Modules\TraceAggregator\Dto\Parameters\TraceTreeInsertParameters;
use App\Modules\TraceAggregator\Repositories\TraceTreeRepositoryInterface;

readonly class TraceAggregatorTreeRepositoryAdapter
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
