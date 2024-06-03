<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObject;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Domain\Entities\Transports\TraceDataFilterTransport;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
use App\Modules\Trace\Repositories\Dto\TraceTimestampsDto;
use App\Modules\Trace\Repositories\Interfaces\TraceTimestampsRepositoryInterface;

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
            data: TraceDataFilterTransport::toDtoIfNotNull($parameters->data),
            hasProfiling: $parameters->hasProfiling,
        );

        $maxCount = collect($timestampsDtoList)->max(
            fn(TraceTimestampsDto $dto) => $dto->count
        ) ?: 10;

        $maxDuration = collect($timestampsDtoList)->max(
            fn(TraceTimestampsDto $dto) => $dto->durationAvg
        ) ?: 10;

        $items = $this->traceTimestampMetricsFactory->makeTimeLine(
            dateFrom: $loggedAtFrom,
            dateTo: $loggedAtTo,
            timestamp: $timestampStep,
            existsTimestamps: array_map(
                fn(TraceTimestampsDto $dto) => new TraceTimestampsObject(
                    timestamp: $dto->timestamp,
                    timestampTo: $this->traceTimestampMetricsFactory->makeNextTimestamp(
                        date: $dto->timestamp,
                        timestamp: $timestampStep
                    ),
                    count: $dto->count,
                    durationPercent: ceil(($dto->durationAvg / $maxDuration) * $maxCount)
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
