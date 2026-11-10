<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpTraceAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolProperty;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolPropertyTypeEnum;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;
use App\Modules\Mcp\Entities\Trace\Data\TraceDataObject;

readonly class GetTraceDataTool implements McpToolInterface
{
    public function __construct(
        private FindMcpTraceAction $findMcpTraceAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_trace_data';
    }

    public function title(): string
    {
        return 'Get trace data';
    }

    public function description(): string
    {
        return 'The full payload (data) of one trace, exactly as the SLogger UI shows it: a tree of '
            . 'key, value and children, not truncated. Call it only for traces you examine.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema(
            properties: [
                new McpToolProperty(
                    name: 'trace_id',
                    type: McpToolPropertyTypeEnum::String,
                    description: 'Trace id.',
                    required: true,
                    max: 255,
                ),
            ],
        );
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $traceId = $arguments->string('trace_id');
        $trace   = $this->findMcpTraceAction->handle($traceId);

        if (is_null($trace)) {
            return $this->formatter->traceNotFound($traceId);
        }

        return new McpToolResult(
            data: [
                'trace_id' => $trace->traceId,
                'data'     => $this->data($trace->data),
            ],
            truncate: false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function data(TraceDataObject $traceDataObject): array
    {
        return [
            'key'             => $traceDataObject->key,
            'value'           => $traceDataObject->value,
            'children'        => $traceDataObject->children === null ? null : array_map(
                fn(TraceDataObject $child): array => $this->data($child),
                $traceDataObject->children
            ),
            'filter_key'      => $traceDataObject->filterKey,
            'can_be_filtered' => $traceDataObject->canBeFiltered,
        ];
    }
}
