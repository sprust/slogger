<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceGroupsAction;
use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceGroupByInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpTraceGroupsParameters;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;

readonly class AggregateTracesTool implements McpToolInterface
{
    private const int MAX_FILTER_VALUES = 20;
    private const int MAX_DATA_FILTER   = 3;

    public function __construct(
        private FindMcpTraceGroupsAction $findMcpTraceGroupsAction,
        private McpToolTraceScopeReader $scopeReader,
        private McpToolServiceFinder $serviceFinder,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'aggregate_traces';
    }

    public function title(): string
    {
        return 'Top trace groups';
    }

    public function description(): string
    {
        return sprintf(
            'Traces over a period grouped by 1 to %d of: service, type, status, hour, minute10 (at most one of '
            . 'the two times), with count, average, p95 and max duration and the slowest trace of each group. '
            . 'Groups by time come in time order, the rest most frequent first; at most %d groups. Use it '
            . 'first for overview questions (which services and types, when failures happen, how latency '
            . 'changes), then search_traces for the traces themselves. Takes the filters of search_traces, '
            . 'data_filter included.',
            FindMcpTraceGroupsAction::MAX_FIELDS,
            FindMcpTraceGroupsAction::LIMIT
        );
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            ...$this->scopeReader->properties(),
            new McpToolProperty(
                name: 'by',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Fields to group by.',
                required: true,
                enum: array_map(
                    static fn(TraceGroupFieldEnum $field) => $field->value,
                    TraceGroupFieldEnum::cases()
                ),
                min: 1,
                max: FindMcpTraceGroupsAction::MAX_FIELDS
            ),
            new McpToolProperty(
                name: 'types',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace types.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'statuses',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace statuses, for example failed.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'tags',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Trace tags, all must be on a trace.',
                max: self::MAX_FILTER_VALUES
            ),
            new McpToolProperty(
                name: 'duration_from',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Minimal duration, in the units of get_trace.',
                min: 0,
                max: McpToolTraceScopeReader::MAX_DURATION
            ),
            new McpToolProperty(
                name: 'duration_to',
                type: McpToolPropertyTypeEnum::Number,
                description: 'Maximal duration, in the units of get_trace.',
                min: 0,
                max: McpToolTraceScopeReader::MAX_DURATION
            ),
            new McpToolProperty(
                name: 'data_filter',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Conditions on the trace data, all must hold. ' . McpTraceDataFilterParser::FORMAT,
                max: self::MAX_DATA_FILTER
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $scope = $this->scopeReader->read($arguments);

        if ($scope instanceof McpToolResult) {
            return $scope;
        }

        $groupBy = $arguments->stringList('by');

        try {
            $groups = $this->findMcpTraceGroupsAction->handle(
                new FindMcpTraceGroupsParameters(
                    serviceIds: $scope->serviceIds,
                    period: $scope->period,
                    groupBy: $groupBy,
                    types: $arguments->stringList('types'),
                    tags: $arguments->stringList('tags'),
                    statuses: $arguments->stringList('statuses'),
                    durationFrom: $arguments->floatNull('duration_from'),
                    durationTo: $arguments->floatNull('duration_to'),
                    dataFilter: $arguments->stringList('data_filter')
                )
            );
        } catch (McpTraceGroupByInvalidException $exception) {
            return $this->formatter->error(error: 'invalid_group_by', hint: $exception->getMessage());
        } catch (McpTraceDataFilterInvalidException $exception) {
            return $this->formatter->error(
                error: 'invalid_data_filter',
                hint: sprintf(
                    'Condition "%s" is not valid. Format: %s',
                    $exception->condition,
                    McpTraceDataFilterParser::FORMAT
                )
            );
        }

        $names = $this->serviceFinder->names();

        return new McpToolResult(
            data: [
                ...$this->scopeReader->periodData($scope),
                'by'        => $groupBy,
                'groups'    => array_map(
                    fn(TraceGroupObject $group) => $this->group($group, $groupBy, $names),
                    $groups->items
                ),
                'truncated' => $groups->truncated,
            ]
        );
    }

    /**
     * @param string[] $groupBy
     *
     * @return array<string, mixed>
     */
    private function group(TraceGroupObject $group, array $groupBy, McpToolServiceNames $names): array
    {
        $data = [];

        foreach ($groupBy as $field) {
            $data[$field] = match (TraceGroupFieldEnum::from($field)) {
                TraceGroupFieldEnum::Service  => $names->describe($group->serviceId),
                TraceGroupFieldEnum::Type     => $group->type,
                TraceGroupFieldEnum::Status   => $group->status,
                TraceGroupFieldEnum::Hour,
                TraceGroupFieldEnum::Minute10 => $this->formatter->time($group->startedAt),
            };
        }

        return [
            ...$data,
            'count'            => $group->count,
            'duration_avg'     => $group->durationAvg,
            'duration_p95'     => $group->durationP95,
            'duration_max'     => $group->durationMax,
            'example_trace_id' => $group->exampleTraceId,
        ];
    }
}
