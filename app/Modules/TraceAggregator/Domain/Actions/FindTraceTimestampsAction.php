<?php

namespace App\Modules\TraceAggregator\Domain\Actions;

use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTimestampsObject;
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

    public function handle(FindTraceTimestampsParameters $parameters): array
    {
        $loggedAtTo = $parameters->loggedAtTo ?? now('UTC');

        $loggedAtFrom    = $this->traceTimestampMetricsFactory->calcLoggedAtFrom(
            timestampPeriod: $parameters->timestampPeriod,
            date: $loggedAtTo
        );
        $timestampMetric = $this->traceTimestampMetricsFactory->calcTimestampMetric(
            timestampPeriod: $parameters->timestampPeriod,
        );

        $timestampsDtoList = $this->traceTimestampsRepository->find(
            timestampMetric: $timestampMetric,
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

        return $this->traceTimestampMetricsFactory->makeTimeLine(
            dateFrom: $loggedAtFrom,
            dateTo: $loggedAtTo,
            timestampMetric: $timestampMetric,
            existsTimestamps: array_map(
                fn(TraceTimestampsDto $dto) => new TraceTimestampsObject(
                    timestamp: $dto->timestamp,
                    count: $dto->count,
                ),
                $timestampsDtoList
            )
        );
    }
}
