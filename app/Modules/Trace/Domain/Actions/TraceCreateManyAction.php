<?php

namespace App\Modules\Trace\Domain\Actions;

use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\Trace\Repositories\Dto\TraceTreeDto;
use App\Modules\Trace\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;

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
