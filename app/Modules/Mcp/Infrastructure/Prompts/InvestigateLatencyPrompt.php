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
                description: 'Period to look at, in words or times, within the time traces are kept, '
                    . 'for example "last 3 hours".',
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
            '1. get_services to resolve the service id.',
            '2. aggregate_traces by ["minute10"] (or ["hour"] for a long period)'
            . (is_null($type) ? '' : sprintf(' with types ["%s"]', $type))
            . ' to find the window where duration_p95 grows.',
            '3. In that window: aggregate_traces by ["service", "type"] for the slowest groups, then '
            . 'search_traces with duration_from near their duration_p95 for the slowest traces.',
            '4. For a few slow traces: get_trace_tree and search_trace_tree to find the slow calls inside; '
            . 'get_trace_data only for the traces that explain the delay.',
            '',
            'Answer with the cause, the time window, the trace ids behind every claim and what was not checked.',
        ]);
    }
}
