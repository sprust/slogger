<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\Data\TraceMetricFieldsFilterDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampFieldDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampFieldIndicatorDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampsDto;
use App\Modules\Trace\Repositories\Dto\Timestamp\TraceTimestampsListDto;
use App\Modules\Trace\Repositories\Interfaces\TraceTimestampsRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

readonly class TraceTimestampsRepository implements TraceTimestampsRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function find(
        TraceTimestampEnum $timestamp,
        array $fields,
        ?array $dataFields = null,
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
        ?array $sort = null,
    ): TraceTimestampsListDto {
        $timestampField = $timestamp->value;

        $match = [
            "timestamps.$timestampField" => [
                '$exists' => true,
            ],
        ];

        $mql = $this->traceQueryBuilder
            ->make(
                serviceIds: $serviceIds,
                traceIds: $traceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
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
            )
            ->toMql();


        foreach ($mql['find'][0] ?? [] as $key => $value) {
            $match[$key] = $value;
        }

        $pipeline = [
            [
                '$match' => $match,
            ],
        ];

        $groups = [];

        foreach ($fields as $field) {
            $this->injectAggregationToGroups($groups, $field);
        }

        foreach ($dataFields ?? [] as $dataField) {
            $fieldName = "data.$dataField->field";

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
                    'timestamp' => "\$timestamps.$timestampField",
                ],
                ...$groupsQuery,
            ],
        ];

        $pipeline[] = [
            '$sort' => [
                '_id.timestamp' => 1,
            ],
        ];

        $aggregation = Trace::collection()->aggregate($pipeline);

        $metrics = [];

        foreach ($aggregation as $item) {
            $groupIndicatorsDtoList = [];

            foreach ($groupsMatch as $fieldName => $aggregators) {
                $indicators = [];

                foreach ($aggregators as $key => $aggregator) {
                    $indicators[] = new TraceTimestampFieldIndicatorDto(
                        name: $aggregator,
                        value: round(is_numeric($item->{$key}) ? $item->{$key} : 0, 6),
                    );
                }

                $groupIndicatorsDtoList[] = new TraceTimestampFieldDto(
                    field: $fieldName,
                    indicators: $indicators
                );
            }

            $metrics[] = new TraceTimestampsDto(
                timestamp: new Carbon($item->_id->timestamp->toDateTime()),
                indicators: $groupIndicatorsDtoList
            );
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
            TraceMetricFieldEnum::Duration => 'duration',
            TraceMetricFieldEnum::Memory => 'memory',
            TraceMetricFieldEnum::Cpu => 'cpu',
            default => throw new RuntimeException("Unknown field [{$field->field->value}]")
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

            throw new RuntimeException("Unknown aggregator [$aggregation->value]");
        }
    }
}
