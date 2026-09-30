<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools;

use App\Modules\Mcp\Domain\Actions\Bridges\FindMcpDataRangeAction;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolArguments;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolInterface;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolResult;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolSchema;

readonly class GetTraceTimeRangeTool implements McpToolInterface
{
    public function __construct(
        private FindMcpDataRangeAction $findMcpDataRangeAction,
        private McpToolFormatter $formatter
    ) {
    }

    public function name(): string
    {
        return 'get_trace_time_range';
    }

    public function title(): string
    {
        return 'Trace time range';
    }

    public function description(): string
    {
        return 'The first and the last hour (UTC) for which traces are stored. '
            . 'Both are null when there are no traces.';
    }

    public function schema(): McpToolSchema
    {
        return new McpToolSchema();
    }

    public function call(McpToolArguments $arguments): McpToolResult
    {
        $range = $this->findMcpDataRangeAction->handle();

        return new McpToolResult(
            data: [
                'first_hour' => $this->formatter->time($range->firstHour),
                'last_hour'  => $this->formatter->time($range->lastHour),
            ]
        );
    }
}
