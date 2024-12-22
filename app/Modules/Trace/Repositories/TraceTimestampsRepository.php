<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Contracts\Repositories\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampFieldIndicatorDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsListDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use RuntimeException;

readonly class TraceTimestampsRepository implements TraceTimestampsRepositoryInterface
{
    public function __construct(
        private TracePipelineBuilder $tracePipelineBuilder,
        private PeriodicTraceService $periodicTraceService
    ) {
    }

    public function find(
        Carbon $loggedAtFrom,
        Carbon $loggedAtTo,
        TraceTimestampEnum $timestamp,
        array $fields,
        ?array $dataFields = null,
        ?array $serviceIds = null,
        ?array $traceIds = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
        ?array $sort = null,
    ): TraceTimestampsListDto {
        $collectionNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo
        );

        if (!count($collectionNames)) {
            return new TraceTimestampsListDto(
                timestamps: [],
                emptyIndicators: [],
            );
        }

        $timestampField = $timestamp->value;

        $timestampFieldKey = "tss.$timestampField";

        $pipeline = $this->tracePipelineBuilder->make(
            serviceIds: $serviceIds,
            traceIds: $traceIds,
            types: $types,
            tags: $tags,
            statuses: $statuses,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
            data: $data,
            hasProfiling: $hasProfiling,
            customMatch: [
                '$and' => [
                    [
                        $timestampFieldKey => [
                            '$gte' => new UTCDateTime($loggedAtFrom),
                        ],
                    ],
                    [
                        $timestampFieldKey => [
                            '$lte' => new UTCDateTime($loggedAtTo),
                        ],
                    ],
                ],
            ]
        );

        $groups = [];

        foreach ($fields as $field) {
            $this->injectAggregationToGroups($groups, $field);
        }

        foreach ($dataFields ?? [] as $dataField) {
            $fieldName = "dt.$dataField->field";

            $groups[$fieldName] = [];

            $this->injectAggregation(
                aggregations: $groups[$fieldName],
                fieldName: $fieldName,
                fieldAggregations: $dataField->aggregations
            );
        }

        $groupsMatch = [];
        $groupsQuery = [];

        foreach ($groups as $fieldName => $aggregations) {
            $groupsMatch[$fieldName] = [];

            foreach ($aggregations as $operator => $operatorValue) {
                $aggregatorKey = Str::uuid()->toString();

                $groupsQuery[$aggregatorKey] = [
                    $operator => $operatorValue,
                ];

                $groupsMatch[$fieldName][$aggregatorKey] = Str::slug($operator);
            }
        }

        $pipeline[] = [
            '$group' => [
                '_id' => [
                    'timestamp' => "\$tss.$timestampField",
                ],
                ...$groupsQuery,
            ],
        ];

        $metrics = [];

        foreach ($collectionNames as $collectionName) {
            $cursor = $this->periodicTraceService->aggregate(
                collectionName: $collectionName,
                pipeline: $pipeline
            );

            foreach ($cursor as $item) {
                $groupIndicatorsDtoList = [];

                foreach ($groupsMatch as $fieldName => $aggregators) {
                    $indicators = [];

                    foreach ($aggregators as $key => $aggregator) {
                        $indicators[] = new TraceTimestampFieldIndicatorDto(
                            name: $aggregator,
                            value: round(is_numeric($item[$key]) ? $item[$key] : 0, 6),
                        );
                    }

                    $groupIndicatorsDtoList[] = new TraceTimestampFieldDto(
                        field: $fieldName,
                        indicators: $indicators
                    );
                }

                $metrics[] = new TraceTimestampsDto(
                    timestamp: new Carbon($item['_id']['timestamp']->toDateTime()),
                    indicators: $groupIndicatorsDtoList
                );
            }
        }

        $emptyIndicators = [];

        foreach ($groupsMatch as $fieldName => $aggregators) {
            $indicators = [];

            foreach ($aggregators as $aggregator) {
                $indicators[] = new TraceTimestampFieldIndicatorDto(
                    name: $aggregator,
                    value: 0,
                );
            }

            $emptyIndicators[] = new TraceTimestampFieldDto(
                field: $fieldName,
                indicators: $indicators
            );
        }

        return new TraceTimestampsListDto(
            timestamps: $metrics,
            emptyIndicators: $emptyIndicators
        );
    }

    private function injectAggregationToGroups(array &$groups, TraceMetricFieldsFilterDto $field): void
    {
        $fieldName = match ($field->field) {
            TraceMetricFieldEnum::Count => 'count',
            TraceMetricFieldEnum::Duration => 'dur',
            TraceMetricFieldEnum::Memory => 'mem',
            TraceMetricFieldEnum::Cpu => 'cpu',
        };

        $aggregations = [];

        $this->injectAggregation(
            aggregations: $aggregations,
            fieldName: $fieldName,
            fieldAggregations: $field->aggregations
        );

        $groups[$fieldName] = $aggregations;
    }

    /**
     * @param TraceMetricFieldAggregatorEnum[] $fieldAggregations
     */
    private function injectAggregation(array &$aggregations, string $fieldName, array $fieldAggregations): void
    {
        foreach ($fieldAggregations as $aggregation) {
            if ($aggregation === TraceMetricFieldAggregatorEnum::Sum) {
                $aggregations['$sum'] = 1;

                continue;
            }

            if ($aggregation === TraceMetricFieldAggregatorEnum::Avg) {
                $aggregations['$avg'] = "\$$fieldName";

                continue;
            }

            if ($aggregation === TraceMetricFieldAggregatorEnum::Min) {
                $aggregations['$min'] = "\$$fieldName";

                continue;
            }

            if ($aggregation === TraceMetricFieldAggregatorEnum::Max) {
                $aggregations['$max'] = "\$$fieldName";

                continue;
            }

            /**
             * @phpstan-ignore-next-line
             * No error to ignore is reported
             */
            throw new RuntimeException("Unknown aggregator [$aggregation->value]");
        }
    }
}
