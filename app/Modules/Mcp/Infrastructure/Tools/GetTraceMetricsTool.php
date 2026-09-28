<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpServicesAction;
use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceMetricsAction;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceMetricsStepNotAllowedException;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpTraceMetricsParameters;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldIndicatorObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampFieldObject;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObject;
use App\Modules\Trace\Enums\TraceMetricFieldEnum;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Enums\TraceTimestampPeriodEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Throwable;

readonly class GetTraceMetricsTool implements McpToolInterface
{
    private const int MAX_FILTER_VALUES = 20;

    private const string ISO_8601 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?$/';

    private const array INDICATORS = ['avg', 'min', 'max', 'p50', 'p95', 'p99'];

    public function __construct(
        private FindMcpTraceMetricsAction $findMcpTraceMetricsAction,
        private FindMcpServicesAction $findMcpServicesAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_trace_metrics';
    }

    public function title(): string
    {
        return 'Get trace metrics';
    }

    public function description(): string
    {
        return 'Trace metrics over a period, step by step, exactly as the graph on the SLogger traces page: '
            . 'count of traces and avg/min/max/p50/p95/p99 of duration, memory and cpu, '
            . 'under filters by services, types, tags, statuses and ranges. Every step of the period '
            . 'is returned, empty ones too. It builds a trace dynamic index in the background: while '
            . 'it is building the answer is "index_building", repeat the SAME call later. The index '
            . 'depends on which filters are set, the step and the hours of the period, not on the '
            . 'filter values: changing services, types, tags or statuses with the same set of filters, '
            . 'step and period reuses the index, a new set of filters, step or hour builds a new one.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            new McpToolProperty(
                name: 'period',
                type: McpToolPropertyTypeEnum::String,
                description: 'Period preset ending at "to".',
                required: true,
                enum: array_map(
                    static fn(TraceTimestampPeriodEnum $period) => $period->value,
                    FindMcpTraceMetricsAction::PERIODS
                )
            ),
            new McpToolProperty(
                name: 'to',
                type: McpToolPropertyTypeEnum::String,
                description: 'End of the period, ISO 8601. Now by default.',
                max: 64
            ),
            new McpToolProperty(
                name: 'step',
                type: McpToolPropertyTypeEnum::String,
                description: 'Step of the series; each period allows its own steps. '
                . 'Keep the default unless you need another one: a new step builds a new index.',
                enum: array_map(
                    static fn(TraceTimestampEnum $step) => $step->value,
                    TraceTimestampEnum::cases()
                )
            ),
            new McpToolProperty(
                name: 'metrics',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Metrics to return. count and duration by default.',
                enum: array_map(
                    static fn(TraceMetricFieldEnum $field) => $field->value,
                    TraceMetricFieldEnum::cases()
                )
            ),
            new McpToolProperty(
                name: 'service_ids',
                type: McpToolPropertyTypeEnum::IntegerList,
                description: 'Service ids from list_services.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'types',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace types.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'tags',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace tags.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'statuses',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace statuses, for example failed.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'duration_from',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Minimal duration, in the units of get_trace.',
                min: 0
            ),
            new McpToolProperty(
                name: 'duration_to',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Maximal duration, in the units of get_trace.',
                min: 0
            ),
            new McpToolProperty(
                name: 'memory_from',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Minimal memory, in the units of get_trace.',
                min: 0
            ),
            new McpToolProperty(
                name: 'memory_to',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Maximal memory, in the units of get_trace.',
                min: 0
            ),
            new McpToolProperty(
                name: 'cpu_from',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Minimal cpu, in the units of get_trace.',
                min: 0
            ),
            new McpToolProperty(
                name: 'cpu_to',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Maximal cpu, in the units of get_trace.',
                min: 0
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $to = null;

        if ($arguments->has('to')) {
            $to = $this->parseTime($arguments->string('to'));

            if (is_null($to)) {
                return $this->formatter->error(
                    error: 'invalid_time',
                    hint: 'Argument "to" is not an ISO 8601 time, for example 2026-09-28T20:00:00Z.'
                );
            }
        }

        $serviceIds = $arguments->intList('service_ids');

        $unknownServiceIds = $this->findUnknownServiceIds($serviceIds);

        if (count($unknownServiceIds) > 0) {
            return $this->formatter->error(
                error: 'service_not_found',
                hint: sprintf(
                    'No services with ids [%s]. Call list_services to get the ids.',
                    implode(', ', $unknownServiceIds)
                )
            );
        }

        $metrics = array_map(
            static fn(string $metric) => TraceMetricFieldEnum::from($metric),
            $arguments->stringList('metrics')
        );

        if (count($metrics) === 0) {
            $metrics = [TraceMetricFieldEnum::Count, TraceMetricFieldEnum::Duration];
        }

        $metrics = array_values(array_unique($metrics, SORT_REGULAR));

        $step = $arguments->stringNull('step');

        try {
            $result = $this->findMcpTraceMetricsAction->handle(
                new FindMcpTraceMetricsParameters(
                    period: TraceTimestampPeriodEnum::from($arguments->string('period')),
                    step: is_null($step) ? null : TraceTimestampEnum::from($step),
                    metrics: $metrics,
                    to: $to,
                    serviceIds: $serviceIds,
                    types: $arguments->stringList('types'),
                    tags: $arguments->stringList('tags'),
                    statuses: $arguments->stringList('statuses'),
                    durationFrom: $arguments->floatNull('duration_from'),
                    durationTo: $arguments->floatNull('duration_to'),
                    memoryFrom: $arguments->floatNull('memory_from'),
                    memoryTo: $arguments->floatNull('memory_to'),
                    cpuFrom: $arguments->floatNull('cpu_from'),
                    cpuTo: $arguments->floatNull('cpu_to')
                )
            );
        } catch (McpTraceIndexBuildingException $exception) {
            return new McpToolResult(
                data: [
                    'status'              => 'index_building',
                    'index_id'            => $exception->indexId,
                    'retry_after_seconds' => 10,
                    'hint'                => 'The trace index for these filters and hours is being built. '
                        . 'Do something else useful meanwhile, then repeat the SAME call. Another set of '
                        . 'filters, another step or period would start building another index.',
                ]
            );
        } catch (McpTraceIndexFailedException $exception) {
            return $this->formatter->error(
                error: 'index_error',
                hint: 'Building the trace index failed: ' . $exception->getMessage()
            );
        } catch (McpTraceMetricsStepNotAllowedException $exception) {
            return $this->formatter->error(
                error: 'step_not_allowed',
                hint: sprintf(
                    'Step "%s" is not allowed for period "%s". Allowed steps: %s.',
                    $exception->step->value,
                    $exception->period->value,
                    implode(', ', array_map(
                        static fn(TraceTimestampEnum $step) => $step->value,
                        $exception->allowedSteps
                    ))
                )
            );
        }

        $items = $result->timestamps->items;

        /** @var TraceTimestampsObject|null $last */
        $last = Arr::last($items);

        return new McpToolResult(
            data: [
                'status'  => 'ready',
                'period'  => $arguments->string('period'),
                'step'    => $result->step->value,
                'from'    => $this->formatter->time($result->timestamps->loggedAtFrom),
                'to'      => $this->formatter->time($last?->timestampTo),
                'metrics' => array_map(
                    static fn(TraceMetricFieldEnum $metric) => $metric->value,
                    $metrics
                ),
                'points'  => array_map(
                    fn(TraceTimestampsObject $item) => $this->point($item, $metrics),
                    $items
                ),
            ]
        );
    }

    private function parseTime(string $value): ?Carbon
    {
        if (!preg_match(self::ISO_8601, $value)) {
            return null;
        }

        try {
            return new Carbon($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param int[] $serviceIds
     *
     * @return int[]
     */
    private function findUnknownServiceIds(array $serviceIds): array
    {
        if (count($serviceIds) === 0) {
            return [];
        }

        $knownIds = array_map(
            static fn(ServiceObject $service) => $service->id,
            $this->findMcpServicesAction->handle(null)
        );

        return array_values(array_diff($serviceIds, $knownIds));
    }

    /**
     * @param TraceMetricFieldEnum[] $metrics
     *
     * @return array<string, mixed>
     */
    private function point(TraceTimestampsObject $item, array $metrics): array
    {
        $point = [
            'from' => $this->formatter->time($item->timestamp),
            'to'   => $this->formatter->time($item->timestampTo),
        ];

        foreach ($metrics as $metric) {
            /** @var TraceTimestampFieldObject|null $field */
            $field = Arr::first(
                $item->fields,
                fn(TraceTimestampFieldObject $field) => $field->field === $this->storedFieldName($metric)
            );

            if ($metric === TraceMetricFieldEnum::Count) {
                $point[$metric->value] = (int) ($this->indicator($field, 'sum') ?? 0);

                continue;
            }

            $values = [];

            foreach (self::INDICATORS as $name) {
                $values[$name] = $this->indicator($field, $name);
            }

            $point[$metric->value] = $values;
        }

        return $point;
    }

    private function indicator(?TraceTimestampFieldObject $field, string $name): int|float|null
    {
        /** @var TraceTimestampFieldIndicatorObject|null $indicator */
        $indicator = Arr::first(
            $field->indicators ?? [],
            static fn(TraceTimestampFieldIndicatorObject $indicator) => $indicator->name === $name
        );

        return $indicator?->value;
    }

    private function storedFieldName(TraceMetricFieldEnum $metric): string
    {
        return match ($metric) {
            TraceMetricFieldEnum::Count    => 'count',
            TraceMetricFieldEnum::Duration => 'dur',
            TraceMetricFieldEnum::Memory   => 'mem',
            TraceMetricFieldEnum::Cpu      => 'cpu',
        };
    }
}
