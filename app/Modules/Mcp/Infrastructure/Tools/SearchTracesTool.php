<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTracesAction;
use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Mcp\Domain\Services\McpTraceDataFilterParser;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpTracesParameters;
use App\Modules\Trace\Entities\Trace\Data\TraceDataAdditionalFieldObject;
use App\Modules\Trace\Entities\Trace\TraceItemObject;
use stdClass;

readonly class SearchTracesTool implements McpToolInterface
{
    private const int MAX_FILTER_VALUES = 20;
    private const int MAX_DATA_FILTER   = 3;
    private const int MAX_DATA_FIELDS   = 10;

    public function __construct(
        private FindMcpTracesAction $findMcpTracesAction,
        private McpToolTraceScopeReader $scopeReader,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'search_traces';
    }

    public function title(): string
    {
        return 'Find traces';
    }

    public function description(): string
    {
        return sprintf(
            'Traces of the chosen services (all when omitted) over a period, newest first, %d per page, '
            . 'with filters, a filter by fields of their data and chosen data fields returned with each '
            . 'trace. Use '
            . 'get_trace_data_fields to learn the data keys first.',
            FindMcpTracesAction::PER_PAGE
        );
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            ...$this->scopeReader->properties(),
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
            new McpToolProperty(
                name: 'data_fields',
                type: McpToolPropertyTypeEnum::StringList,
                description: 'Data keys to return with each trace, for example request.uri.',
                max: self::MAX_DATA_FIELDS
            ),
            new McpToolProperty(
                name: 'page',
                type: McpToolPropertyTypeEnum::Integer,
                description: 'Page number, starting at 1.',
                min: 1
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $scope = $this->scopeReader->read($arguments);

        if ($scope instanceof McpToolResult) {
            return $scope;
        }

        $page = $arguments->intNull('page') ?? 1;

        try {
            $traces = $this->findMcpTracesAction->handle(
                new FindMcpTracesParameters(
                    serviceIds: $scope->serviceIds,
                    period: $scope->period,
                    types: $arguments->stringList('types'),
                    statuses: $arguments->stringList('statuses'),
                    tags: $arguments->stringList('tags'),
                    durationFrom: $arguments->floatNull('duration_from'),
                    durationTo: $arguments->floatNull('duration_to'),
                    dataFilter: $arguments->stringList('data_filter'),
                    dataFields: $arguments->stringList('data_fields'),
                    page: $page
                )
            );
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

        return new McpToolResult(
            data: [
                ...$this->scopeReader->periodData($scope),
                'page'     => $page,
                'has_more' => count($traces->items) >= FindMcpTracesAction::PER_PAGE,
                'traces'   => array_map(
                    fn(TraceItemObject $item) => $this->trace($item),
                    $traces->items
                ),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function trace(TraceItemObject $item): array
    {
        $trace = $item->trace;

        $data = new stdClass();

        foreach ($trace->additionalFields as $field) {
            /** @var TraceDataAdditionalFieldObject $field */
            $data->{$field->key} = $field->values[0] ?? null;
        }

        return [
            'trace_id'        => $trace->traceId,
            'parent_trace_id' => $trace->parentTraceId,
            'type'            => $trace->type,
            'status'          => $trace->status,
            'tags'            => $trace->tags,
            'duration'        => $trace->duration,
            'memory'          => $trace->memory,
            'cpu'             => $trace->cpu,
            'pid'             => $trace->pid,
            'logged_at'       => $this->formatter->time($trace->loggedAt),
            'data'            => $data,
        ];
    }
}
