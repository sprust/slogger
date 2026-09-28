<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts;

use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptArgument;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptInterface;

readonly class InvestigateLatencyPrompt implements McpPromptInterface
{
    public function name(): string
    {
        return 'investigate_latency';
    }

    public function title(): string
    {
        return 'Investigate latency';
    }

    public function description(): string
    {
        return 'Why traces of a service are slow over a period.';
    }

    public function arguments(): array
    {
        return [
            new McpPromptArgument(
                name: 'service',
                description: 'Service name or id.',
                required: true
            ),
            new McpPromptArgument(
                name: 'period',
                description: 'Period to look at, in words or times, for example "last 3 hours".',
                required: true
            ),
            new McpPromptArgument(
                name: 'type',
                description: 'Trace type to look at, if only one.',
                required: false
            ),
        ];
    }

    public function text(array $arguments): string
    {
        $type = $arguments['type'] ?? null;

        return implode("\n", [
            sprintf(
                'Find out why traces of service "%s"%s are slow over %s.',
                $arguments['service'] ?? '',
                is_null($type) ? '' : sprintf(' of type "%s"', $type),
                $arguments['period'] ?? ''
            ),
            '',
            'Steps:',
            '1. list_services to resolve the service id.',
            '2. get_trace_metrics with metrics ["count", "duration"] over the period'
            . (is_null($type) ? '' : sprintf(' and types ["%s"]', $type))
            . ' to find the window where p95 and p99 of duration grow.',
            '3. In that window: find_traces with duration_from near the p95 for the slowest traces.',
            '4. For a few slow traces: get_trace_tree and find_in_trace_tree to find the slow calls inside; '
            . 'get_trace_data only for the traces that explain the delay.',
            '',
            'Answer with the cause, the time window, the trace ids behind every claim and what was not checked.',
        ]);
    }
}
