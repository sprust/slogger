<?php

namespace App\Modules\TraceCollector\Domain\Actions;

use App\Modules\Common\Events\EventsDispatcher;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParameters;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceCreateParametersList;
use App\Modules\TraceCollector\Domain\Entities\Parameters\TraceTreeCreateParameters;
use App\Modules\TraceCollector\Events\TraceCreatedEvent;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCollector\Repositories\Interfaces\TraceTreeRepositoryInterface;

readonly class TraceCreateManyAction
{
    public function __construct(
        private TraceRepositoryInterface $traceRepository,
        private TraceTreeRepositoryInterface $traceTreeRepository,
        private EventsDispatcher $eventsDispatcher
    ) {
    }

    public function handle(TraceCreateParametersList $parametersList): void
    {
        $this->traceRepository->createMany($parametersList);

        $this->traceTreeRepository->insertMany(
            array_map(
                fn(TraceCreateParameters $traceCreateParameters) => new TraceTreeCreateParameters(
                    traceId: $traceCreateParameters->traceId,
                    parentTraceId: $traceCreateParameters->parentTraceId,
                    loggedAt: $traceCreateParameters->loggedAt
                ),
                $parametersList->getItems()
            )
        );

        array_map(
            fn(TraceCreateParameters $traceCreateParameters) => $this->eventsDispatcher->dispatch(
                new TraceCreatedEvent(
                    serviceId: $traceCreateParameters->serviceId,
                    type: $traceCreateParameters->type,
                    status: $traceCreateParameters->status
                )
            ),
            $parametersList->getItems()
        );
    }
}
