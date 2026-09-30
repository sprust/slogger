<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceDataFieldsAction;
use App\Modules\Mcp\Entities\Bridges\McpTraceDataFieldObject;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Parameters\FindMcpTraceDataFieldsParameters;

readonly class GetTraceDataFieldsTool implements McpToolInterface
{
    public function __construct(
        private FindMcpTraceDataFieldsAction $findMcpTraceDataFieldsAction,
        private McpToolTraceScopeReader $scopeReader,
    ) {
    }

    public function name(): string
    {
        return 'get_trace_data_fields';
    }

    public function title(): string
    {
        return 'List trace data fields';
    }

    public function description(): string
    {
        return sprintf(
            'Keys of the data of the latest %d traces of a type over a period, with an example value each. '
            . 'For data_fields of search_traces use a key as listed. For data_filter drop the index of an '
            . 'array element: items.0.price becomes items.price (any element matches), tags.0 becomes tags; '
            . 'only keys of letters, digits and _ can be filtered on.',
            FindMcpTraceDataFieldsAction::TRACES_COUNT
        );
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema([
            ...$this->scopeReader->properties(),
            new McpToolProperty(
                name: 'type',
                type: McpToolPropertyTypeEnum::String,
                description: 'Trace type.',
                required: true,
                max: 255
            ),
        ]);
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $scope = $this->scopeReader->read($arguments);

        if ($scope instanceof McpToolResult) {
            return $scope;
        }

        $type = $arguments->string('type');

        $result = $this->findMcpTraceDataFieldsAction->handle(
            new FindMcpTraceDataFieldsParameters(
                serviceIds: $scope->serviceIds,
                period: $scope->period,
                type: $type
            )
        );

        return new McpToolResult(
            data: [
                ...$this->scopeReader->periodData($scope),
                'type'         => $type,
                'traces_count' => $result->tracesCount,
                'fields'       => array_map(
                    static fn(McpTraceDataFieldObject $field) => [
                        'key'     => $field->key,
                        'example' => $field->example,
                    ],
                    $result->fields
                ),
            ]
        );
    }
}
