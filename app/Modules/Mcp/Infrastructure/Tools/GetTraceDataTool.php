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
use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Infrastructure\Http\Resources\Data\TraceDataResource;

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
        return new McpToolSchema([
            new McpToolProperty(
                name: 'trace_id',
                type: McpToolPropertyTypeEnum::String,
                description: 'Trace id.',
                required: true,
                max: 255
            ),
        ]);
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
                'data'     => $this->plain(new TraceDataResource($trace->data)),
            ],
            truncate: false
        );
    }

    private function plain(mixed $value): mixed
    {
        if ($value instanceof AbstractApiResource) {
            return $this->plain($value->toArray());
        }

        if (is_array($value)) {
            return array_map(fn(mixed $item) => $this->plain($item), $value);
        }

        return $value;
    }
}
