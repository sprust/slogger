<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampFieldIndicatorObject;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampFieldObject;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObject;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Domain\Entities\Transports\TraceDataFilterTransport;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Repositories\Dto\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\TraceTimestampFieldIndicatorDto;
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
        $fields = $parameters->fields;

        if (empty($fields) && empty($parameters->dataFields)) {
            $fields = [
                TraceMetricFieldEnum::Count,
            ];
        }

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
            fields: $fields,
            dataFields: $parameters->dataFields,
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
                    fields: array_map(
                        fn(TraceTimestampFieldDto $dto) => new TraceTimestampFieldObject(
                            field: $dto->field,
                            indicators: array_map(
                                fn(TraceTimestampFieldIndicatorDto $dto) => new TraceTimestampFieldIndicatorObject(
                                    name: $dto->name,
                                    value: $dto->value
                                ),
                                $dto->indicators
                            ),
                        ),
                        $dto->indicators
                    )
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
