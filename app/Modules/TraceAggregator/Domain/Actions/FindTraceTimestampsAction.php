<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObjects;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\TraceAggregator\Domain\Services\TraceTimestampMetricsFactory;
use App\Modules\TraceAggregator\Repositories\Dto\TraceTimestampsDto;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTimestampsRepositoryInterface;

readonly class FindTraceTimestampsAction
{
    public function __construct(
        private TraceTimestampsRepositoryInterface $traceTimestampsRepository,
        private TraceTimestampMetricsFactory $traceTimestampMetricsFactory
    ) {
    }

    public function handle(FindTraceTimestampsParameters $parameters): TraceTimestampsObjects
    {
        $loggedAtTo = $parameters->loggedAtTo ?? now('UTC');

        $loggedAtFrom = $this->traceTimestampMetricsFactory->calcLoggedAtFrom(
            timestampPeriod: $parameters->timestampPeriod,
            date: $loggedAtTo
        );

        $timestampStep = $parameters->timestampStep;

        $loggedAtFrom = $this->traceTimestampMetricsFactory->prepareDateByTimestamp(
            date: $loggedAtFrom,
            timestamp: $timestampStep
        );

        $timestampsDtoList = $this->traceTimestampsRepository->find(
            timestamp: $timestampStep,
            serviceIds: $parameters->serviceIds,
            traceIds: $parameters->traceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling,
        );

        $items = $this->traceTimestampMetricsFactory->makeTimeLine(
            dateFrom: $loggedAtFrom,
            dateTo: $loggedAtTo,
            timestamp: $timestampStep,
            existsTimestamps: array_map(
                fn(TraceTimestampsDto $dto) => new TraceTimestampsObject(
                    timestamp: $dto->timestamp,
                    count: $dto->count,
                ),
                $timestampsDtoList
            )
        );

        return new TraceTimestampsObjects(
            loggedAtFrom: $loggedAtFrom,
            items: $items
        );
    }
}
