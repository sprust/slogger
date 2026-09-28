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

readonly class GetTraceTool implements McpToolInterface
{
    public function __construct(
        private FindMcpTraceAction $findMcpTraceAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_trace';
    }

    public function title(): string
    {
        return 'Get trace';
    }

    public function description(): string
    {
        return 'Summary of one trace by its id: service, type, status, tags, duration, memory, cpu, '
            . 'time. Without the payload: call get_trace_data for it. No index needed.';
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
                'service'         => $this->formatter->service($trace->service),
                'trace_id'        => $trace->traceId,
                'parent_trace_id' => $trace->parentTraceId,
                'type'            => $trace->type,
                'status'          => $trace->status,
                'tags'            => $trace->tags,
                'duration'        => $trace->duration,
                'memory'          => $trace->memory,
                'cpu'             => $trace->cpu,
                'logged_at'       => $this->formatter->time($trace->loggedAt),
            ]
        );
    }
}
