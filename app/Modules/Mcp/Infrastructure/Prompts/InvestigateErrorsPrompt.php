<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts;

use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptArgument;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptInterface;

readonly class InvestigateErrorsPrompt implements McpPromptInterface
{
    public function name(): string
    {
        return 'investigate_errors';
    }

    public function title(): string
    {
        return 'Investigate errors';
    }

    public function description(): string
    {
        return 'Why traces of a service fail over a period.';
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
        ];
    }

    public function text(array $arguments): string
    {
        return implode("\n", [
            sprintf(
                'Find out why traces of service "%s" fail over %s.',
                $arguments['service'] ?? '',
                $arguments['period'] ?? ''
            ),
            '',
            'Steps:',
            '1. list_services to resolve the service id.',
            '2. get_trace_metrics with statuses ["failed"] over the period to see when failures happen and how many.',
            '3. list_incidents: a watcher may already know when the problem started.',
            '4. In the window with the most failures: trace_facets with statuses ["failed"] for the failing types '
            . 'and tags, then find_traces with statuses ["failed"] for the traces themselves.',
            '5. For a few failed traces: get_trace, get_trace_tree and find_in_trace_tree with statuses ["failed"] '
            . 'to find the call that failed; get_trace_data only for the traces that explain it.',
            '',
            'Answer with the cause, the time window, the trace ids behind every claim and what was not checked.',
        ]);
    }
}
