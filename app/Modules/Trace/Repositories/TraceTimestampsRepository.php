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
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceRowReader;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Modules\Trace\Repositories\Services\TraceTimestampMetricsFactory;
use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

readonly class TraceTimestampsRepository
{
    public function __construct(
        private ClickhouseClient $client,
        private ClickhouseTraceFilterBuilder $filterBuilder,
        private ClickhouseTraceRowReader $rowReader,
        private TraceDataPathResolver $pathResolver,
        private TraceTimestampMetricsFactory $timestampMetricsFactory,
    ) {
    }

    /**
     * The metrics of the traces per step of the timeline.
     *
     * A trace falls into the step its logged-at moment starts; the steps are the same as
     * the ones the receiver used to precompute (`tss`): aligned to the epoch in UTC, days
     * and months to their calendar start.
     *
     * @param int[]|null                            $serviceIds
     * @param string[]|null                         $traceIds
     * @param TraceMetricFieldsFilterDto[]          $fields
     * @param TraceMetricDataFieldsFilterDto[]|null $dataFields
     * @param string[]                              $types
     * @param string[]                              $tags
     * @param string[]                              $statuses
     *
     * @throws ClickhouseQueryException
     * @throws InvalidArgumentException
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
        $condition = $this->filterBuilder->build(
            serviceIds: $serviceIds,
            traceIds: $traceIds,
            // the last step runs to its end, not to the moment it starts
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $this->timestampMetricsFactory->makeNextTimestamp(
                date: $loggedAtTo,
                timestamp: $timestamp
            ),
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
        );

        /**
         * The aggregations of the query by alias: which field and which indicator each is.
         *
         * @var array<string, array{field: string, aggregation: TraceMetricFieldAggregatorEnum}> $aliases
         */
        $aliases = [];

        /** @var string[] $expressions */
        $expressions = [];

        /** @var array<string, TraceMetricFieldAggregatorEnum[]> $fieldAggregations */
        $fieldAggregations = [];

        foreach ($fields as $field) {
            $fieldName = match ($field->field) {
                TraceMetricFieldEnum::Count    => 'count',
                TraceMetricFieldEnum::Duration => 'dur',
                TraceMetricFieldEnum::Memory   => 'mem',
                TraceMetricFieldEnum::Cpu      => 'cpu',
            };

            $fieldAggregations[$fieldName] = $field->aggregations;

            $this->addAggregations($aliases, $expressions, $fieldName, $fieldName, $field->aggregations);
        }

        foreach ($dataFields ?? [] as $dataField) {
            $fieldName = sprintf('dt.%s', $dataField->field);

            $fieldAggregations[$fieldName] = $dataField->aggregations;

            $path = $this->pathResolver->resolve($dataField->field);

            // numbers only, as MongoDB read them: a string under the key adds nothing
            $value = sprintf(
                "if(dynamicType(%s) IN ('Int64', 'UInt64', 'Float64'), accurateCastOrNull(%s, 'Float64'), NULL)",
                $path->objectExpression,
                $path->objectExpression
            );

            $this->addAggregations($aliases, $expressions, $fieldName, $value, $dataField->aggregations);
        }

        $bucket = $this->makeBucketExpression($timestamp);

        $rows = $this->client->select(
            sql: sprintf(
                'SELECT %s AS bucket%s FROM traces FINAL WHERE %s AND %s >= {bucketFrom:DateTime64(6, \'UTC\')} '
                . 'GROUP BY bucket ORDER BY bucket',
                $bucket,
                $expressions === [] ? '' : ', ' . implode(', ', $expressions),
                $condition->sql,
                $bucket
            ),
            params: [
                ...$condition->params,
                'bucketFrom' => $loggedAtFrom,
            ],
            queryIdPrefix: 'trace-timestamps'
        );

        $metrics = [];

        foreach ($rows as $row) {
            $indicators = [];

            foreach ($fieldAggregations as $fieldName => $aggregations) {
                $fieldIndicators = [];

                foreach ($aliases as $alias => $aliasTarget) {
                    if ($aliasTarget['field'] !== $fieldName) {
                        continue;
                    }

                    $fieldIndicators[] = new TraceTimestampFieldIndicatorDto(
                        name: $aliasTarget['aggregation']->value,
                        value: round(is_numeric($row[$alias] ?? null) ? (float) $row[$alias] : 0.0, 6),
                    );
                }

                $indicators[] = new TraceTimestampFieldDto(
                    field: $fieldName,
                    indicators: $fieldIndicators
                );
            }

            $metrics[] = new TraceTimestampsDto(
                timestamp: $this->rowReader->time($row['bucket']),
                indicators: $indicators
            );
        }

        $emptyIndicators = [];

        foreach ($fieldAggregations as $fieldName => $aggregations) {
            $emptyIndicators[] = new TraceTimestampFieldDto(
                field: $fieldName,
                indicators: array_map(
                    static fn(TraceMetricFieldAggregatorEnum $aggregation) => new TraceTimestampFieldIndicatorDto(
                        name: $aggregation->value,
                        value: 0,
                    ),
                    $aggregations
                )
            );
        }

        return new TraceTimestampsListDto(
            timestamps: $metrics,
            emptyIndicators: $emptyIndicators
        );
    }

    /**
     * @param array<string, array{field: string, aggregation: TraceMetricFieldAggregatorEnum}> $aliases
     * @param string[]                                                                         $expressions
     * @param TraceMetricFieldAggregatorEnum[]                                                 $aggregations
     */
    private function addAggregations(
        array &$aliases,
        array &$expressions,
        string $fieldName,
        string $value,
        array $aggregations
    ): void {
        foreach ($aggregations as $aggregation) {
            $alias = 'a' . count($aliases);

            $aliases[$alias] = [
                'field'       => $fieldName,
                'aggregation' => $aggregation,
            ];

            $expressions[] = sprintf(
                '%s AS %s',
                match ($aggregation) {
                    TraceMetricFieldAggregatorEnum::Sum => 'count()',
                    TraceMetricFieldAggregatorEnum::Avg => "avg($value)",
                    TraceMetricFieldAggregatorEnum::Min => "min($value)",
                    TraceMetricFieldAggregatorEnum::Max => "max($value)",
                    TraceMetricFieldAggregatorEnum::P50 => "quantile(0.5)($value)",
                    TraceMetricFieldAggregatorEnum::P95 => "quantile(0.95)($value)",
                    TraceMetricFieldAggregatorEnum::P99 => "quantile(0.99)($value)",
                },
                $alias
            );
        }
    }

    private function makeBucketExpression(TraceTimestampEnum $timestamp): string
    {
        $start = match ($timestamp) {
            TraceTimestampEnum::S5    => 'toStartOfInterval(lat, INTERVAL 5 SECOND)',
            TraceTimestampEnum::S10   => 'toStartOfInterval(lat, INTERVAL 10 SECOND)',
            TraceTimestampEnum::S30   => 'toStartOfInterval(lat, INTERVAL 30 SECOND)',
            TraceTimestampEnum::Min   => 'toStartOfInterval(lat, INTERVAL 1 MINUTE)',
            TraceTimestampEnum::Min5  => 'toStartOfInterval(lat, INTERVAL 5 MINUTE)',
            TraceTimestampEnum::Min10 => 'toStartOfInterval(lat, INTERVAL 10 MINUTE)',
            TraceTimestampEnum::Min30 => 'toStartOfInterval(lat, INTERVAL 30 MINUTE)',
            TraceTimestampEnum::H     => 'toStartOfInterval(lat, INTERVAL 1 HOUR)',
            TraceTimestampEnum::H4    => 'toStartOfInterval(lat, INTERVAL 4 HOUR)',
            TraceTimestampEnum::H12   => 'toStartOfInterval(lat, INTERVAL 12 HOUR)',
            TraceTimestampEnum::D     => 'toStartOfDay(lat)',
            TraceTimestampEnum::M     => 'toStartOfMonth(lat)',
        };

        return "toDateTime64($start, 6, 'UTC')";
    }
}
