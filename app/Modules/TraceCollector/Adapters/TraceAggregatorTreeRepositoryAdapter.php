<?php

namespace App\Modules\TraceCollector\Adapters;

use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeInsertParameters;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceCollector\Dto\Parameters\TraceTreeCreateParameters;

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
