<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObject;
use App\Modules\Trace\Domain\Entities\Objects\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Domain\Entities\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Domain\Entities\Transports\TraceDataFilterTransport;
use App\Modules\Trace\Domain\Entities\Transports\TraceTimestampFieldTransport;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Repositories\Dto\Data\TraceMetricDataFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampsDto;
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
            $fields = TraceMetricFieldEnum::cases();
        }

        $fieldsFilter = [];

        foreach ($fields as $field) {
            if ($field === TraceMetricFieldEnum::Count) {
                $fieldsFilter[] = new TraceMetricFieldsFilterDto(
                    field: $field,
                    aggregations: [
                        TraceMetricFieldAggregatorEnum::Sum,
                    ]
                );

                continue;
            }

            $fieldsFilter[] = new TraceMetricFieldsFilterDto(
                field: $field,
                aggregations: [
                    TraceMetricFieldAggregatorEnum::Avg,
                    TraceMetricFieldAggregatorEnum::Min,
                    TraceMetricFieldAggregatorEnum::Max,
                ]
            );
        }

        /** @var TraceMetricFieldsFilterDto[] $fieldsFilter */

        /** @var TraceMetricDataFieldsFilterDto[]|null $dataFieldsFilter */
        $dataFieldsFilter = null;

        if (!is_null($parameters->dataFields)) {
            $dataFieldsFilter = [];

            foreach ($parameters->dataFields as $dataField) {
                $dataFieldsFilter[] = new TraceMetricDataFieldsFilterDto(
                    field: $dataField,
                    aggregations: [
                        TraceMetricFieldAggregatorEnum::Avg,
                        TraceMetricFieldAggregatorEnum::Min,
                        TraceMetricFieldAggregatorEnum::Max,
                    ]
                );
            }
        }

        $loggedAtTo = $parameters->loggedAtTo ?? now('UTC');

        $loggedAtFrom = $this->traceTimestampMetricsFactory->calcLoggedAtFrom(
            timestampPeriod: $parameters->timestampPeriod,
            date: $loggedAtTo
        );

        $loggedAtFrom = $this->traceTimestampMetricsFactory->prepareDateByTimestamp(
            date: $loggedAtFrom,
            timestamp: $parameters->timestampStep
        );

        $timestampsDtoList = $this->traceTimestampsRepository->find(
            timestamp: $parameters->timestampStep,
            fields: $fieldsFilter,
            dataFields: $dataFieldsFilter,
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
            timestamp: $parameters->timestampStep,
            emptyIndicators: array_map(
                fn(TraceTimestampFieldDto $dto) => TraceTimestampFieldTransport::toObject($dto),
                $timestampsDtoList->emptyIndicators
            ),
            existsTimestamps: array_map(
                fn(TraceTimestampsDto $dto) => new TraceTimestampsObject(
                    timestamp: $dto->timestamp,
                    timestampTo: $this->traceTimestampMetricsFactory->makeNextTimestamp(
                        date: $dto->timestamp,
                        timestamp: $parameters->timestampStep
                    ),
                    fields: array_map(
                        fn(TraceTimestampFieldDto $dto) => TraceTimestampFieldTransport::toObject($dto),
                        $dto->indicators
                    )
                ),
                $timestampsDtoList->timestamps
            )
        );

        return new TraceTimestampsObjects(
            loggedAtFrom: $loggedAtFrom,
            items: $items
        );
    }
}
