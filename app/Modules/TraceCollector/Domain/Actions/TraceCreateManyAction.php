<?php

namespace App\Modules\TraceCollector\Domain\Actions;

use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Repositories\Dto\TraceTreeDto;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceTreeRepositoryInterface;

readonly class TraceCreateManyAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository
    ) {
    }

    public function handle(TraceCreateParametersList $parametersList): void
    {
        $this->traceRepository->createMany($parametersList);

        $this->traceTreeRepository->insertMany(
            array_map(
                fn(TraceCreateParameters $traceCreateParameters) => new TraceTreeDto(
                    traceId: $traceCreateParameters->traceId,
                    parentTraceId: $traceCreateParameters->parentTraceId,
                    loggedAt: $traceCreateParameters->loggedAt
                ),
                $parametersList->getItems()
            )
        );
    }
}
