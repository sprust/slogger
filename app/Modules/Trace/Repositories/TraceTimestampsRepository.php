<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricDataFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampFieldIndicatorDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsDto;
use App\Modules\Trace\Repositories\Dto\Trace\Timestamp\TraceTimestampsListDto;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TraceMetricAggregationFactory;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SConcur\Bson\UTCDateTime;

readonly class TraceTimestampsRepository
{
    public function __construct(
        private TracePipelineBuilder $tracePipelineBuilder,
        private PeriodicTraceService $periodicTraceService,
        private TraceMetricAggregationFactory $aggregationFactory
    ) {
    }

    /**
     * @param int[]|null                            $serviceIds
     * @param string[]|null                         $traceIds
     * @param TraceMetricFieldsFilterDto[]          $fields
     * @param TraceMetricDataFieldsFilterDto[]|null $dataFields
     * @param string[]                              $types
     * @param string[]                              $tags
     * @param string[]                              $statuses
     */
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
        ?bool $hasProfiling = null
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

        /** @var array<string, array<string, array<string, mixed>>> $groups */
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

            foreach ($aggregations as $name => $expression) {
                $aggregatorKey = Str::uuid()->toString();

                $groupsQuery[$aggregatorKey] = $expression;

                $groupsMatch[$fieldName][$aggregatorKey] = $name;
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
                            value: round($this->aggregationFactory->readValue($item[$key] ?? null), 6),
                        );
                    }

                    $groupIndicatorsDtoList[] = new TraceTimestampFieldDto(
                        field: $fieldName,
                        indicators: $indicators
                    );
                }

                /** @var UTCDateTime $metricTimestamp */
                $metricTimestamp = $item['_id']['timestamp'];

                $metrics[] = new TraceTimestampsDto(
                    timestamp: new Carbon($metricTimestamp->toDateTime()),
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

    /**
     * @param array<string, array<string, array<string, mixed>>> $groups
     */
    private function injectAggregationToGroups(array &$groups, TraceMetricFieldsFilterDto $field): void
    {
        $fieldName = match ($field->field) {
            TraceMetricFieldEnum::Count    => 'count',
            TraceMetricFieldEnum::Duration => 'dur',
            TraceMetricFieldEnum::Memory   => 'mem',
            TraceMetricFieldEnum::Cpu      => 'cpu',
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
     * @param array<string, array<string, mixed>> $aggregations
     * @param TraceMetricFieldAggregatorEnum[]    $fieldAggregations
     */
    private function injectAggregation(array &$aggregations, string $fieldName, array $fieldAggregations): void
    {
        foreach ($fieldAggregations as $aggregation) {
            $aggregations[$aggregation->value] = $this->aggregationFactory->makeExpression(
                aggregation: $aggregation,
                fieldName: $fieldName
            );
        }
    }
}
