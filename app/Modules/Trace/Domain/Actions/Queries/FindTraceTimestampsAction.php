<?php

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Contracts\Actions\Queries\FindTraceTimestampsActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Domain\Services\TraceTimestampMetricsFactory;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldIndicatorObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObjects;
use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Parameters\FindTraceTimestampsParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricDataFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampFieldIndicatorDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsListDto;
use Illuminate\Support\Arr;
use RrParallel\Exceptions\ParallelJobsException;
use RrParallel\Exceptions\WaitTimeoutException;
use RrParallel\Services\Dto\JobResultsDto;
use RrParallel\Services\ParallelPusherInterface;
use RuntimeException;

readonly class FindTraceTimestampsAction implements FindTraceTimestampsActionInterface
{
    public function __construct(
        private TraceTimestampMetricsFactory $traceTimestampMetricsFactory,
        private TraceDynamicIndexInitializer $traceDynamicIndexInitializer,
        private ParallelPusherInterface $parallelPusher
    ) {
    }

    /**
     * @throws TraceDynamicIndexErrorException
     * @throws TraceDynamicIndexInProcessException
     * @throws ParallelJobsException
     * @throws WaitTimeoutException
     * @throws TraceDynamicIndexNotInitException
     */
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

        $data = $parameters->data;

        $this->traceDynamicIndexInitializer->init(
            serviceIds: $parameters->serviceIds,
            timestampStep: $parameters->timestampStep,
            traceIds: $parameters->traceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $parameters->types,
            tags: $parameters->tags,
            statuses: $parameters->statuses,
            durationFrom: $parameters->durationFrom,
            durationTo: $parameters->durationTo,
            memoryFrom: $parameters->memoryFrom,
            memoryTo: $parameters->memoryTo,
            cpuFrom: $parameters->cpuFrom,
            cpuTo: $parameters->cpuTo,
            data: $data,
            hasProfiling: $parameters->hasProfiling,
        );

        $stepInSeconds = (int) ceil($loggedAtFrom->diffInSeconds($loggedAtTo) / 10);

        $callbacks = [];

        $dateCursor = $loggedAtFrom->clone();

        while ($dateCursor->lte($loggedAtTo)) {
            $from = $dateCursor->clone();
            $to   = $from->clone()->addSeconds($stepInSeconds);

            if ($to->gt($loggedAtTo)) {
                $to = $loggedAtTo->clone();
            }

            $callbacks[] = static fn(TraceTimestampsRepositoryInterface $repository) => $repository->find(
                loggedAtFrom: $from,
                loggedAtTo: $to,
                timestamp: $parameters->timestampStep,
                fields: $fieldsFilter,
                dataFields: $dataFieldsFilter,
                serviceIds: $parameters->serviceIds,
                traceIds: $parameters->traceIds,
                types: $parameters->types,
                tags: $parameters->tags,
                statuses: $parameters->statuses,
                durationFrom: $parameters->durationFrom,
                durationTo: $parameters->durationTo,
                memoryFrom: $parameters->memoryFrom,
                memoryTo: $parameters->memoryTo,
                cpuFrom: $parameters->cpuFrom,
                cpuTo: $parameters->cpuTo,
                data: $data,
                hasProfiling: $parameters->hasProfiling,
            );

            $dateCursor = $to->clone()->addMicrosecond();
        }

        $waitGroup = $this->parallelPusher->wait($callbacks);

        /** @var JobResultsDto<TraceTimestampsListDto> $results */
        $results = $waitGroup->wait(20);

        // TODO: normal error handling
        if ($results->hasFailed) {
            $jobResultError = Arr::first($results->failed)->error;

            if (!$jobResultError) {
                throw new RuntimeException('Unknown error');
            }

            throw new RuntimeException(
                $jobResultError->message . PHP_EOL . $jobResultError->traceAsString
            );
        }

        /** @var TraceTimestampsDto[] $timestampsResult */
        $timestampsResult = [];
        /** @var TraceTimestampFieldDto[] $emptyIndicatorsResult */
        $emptyIndicatorsResult = [];

        foreach ($results->results as $result) {
            foreach ($result->result->timestamps as $foundTimestamp) {
                $existsTimestamp = Arr::first(
                    $timestampsResult,
                    fn(TraceTimestampsDto $timestampResult) => $timestampResult->timestamp
                        ->eq($foundTimestamp->timestamp)
                );

                if ($existsTimestamp) {
                    continue;
                }

                $timestampsResult[] = $foundTimestamp;
            }

            foreach ($result->result->emptyIndicators as $foundEmptyIndicator) {
                $existsEmptyIndicator = Arr::first(
                    $emptyIndicatorsResult,
                    fn(TraceTimestampFieldDto $emptyIndicatorResult) => $emptyIndicatorResult->field
                        === $foundEmptyIndicator->field
                );

                if ($existsEmptyIndicator) {
                    continue;
                }

                $emptyIndicatorsResult[] = $foundEmptyIndicator;
            }
        }

        $timestampsResult = Arr::sort(
            $timestampsResult,
            fn(TraceTimestampsDto $timestampResult) => $timestampResult->timestamp->getTimestampMs()
        );

        $items = $this->traceTimestampMetricsFactory->makeTimeLine(
            dateFrom: $loggedAtFrom,
            dateTo: $loggedAtTo,
            timestamp: $parameters->timestampStep,
            emptyIndicators: array_map(
                fn(TraceTimestampFieldDto $dto) => $this->transportField($dto),
                $emptyIndicatorsResult
            ),
            existsTimestamps: array_map(
                fn(TraceTimestampsDto $dto) => new TraceTimestampsObject(
                    timestamp: $dto->timestamp,
                    timestampTo: $this->traceTimestampMetricsFactory->makeNextTimestamp(
                        date: $dto->timestamp,
                        timestamp: $parameters->timestampStep
                    ),
                    fields: array_map(
                        fn(TraceTimestampFieldDto $dto) => $this->transportField($dto),
                        $dto->indicators
                    )
                ),
                $timestampsResult
            )
        );

        return new TraceTimestampsObjects(
            loggedAtFrom: $loggedAtFrom,
            items: $items
        );
    }

    private function transportField(TraceTimestampFieldDto $dto): TraceTimestampFieldObject
    {
        return new TraceTimestampFieldObject(
            field: $dto->field,
            indicators: array_map(
                fn(TraceTimestampFieldIndicatorDto $dto) => new TraceTimestampFieldIndicatorObject(
                    name: $dto->name,
                    value: $dto->value
                ),
                $dto->indicators
            ),
        );
    }
}
