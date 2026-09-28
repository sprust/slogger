<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts;

use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptArgument;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptInterface;

readonly class ExplainIncidentPrompt implements McpPromptInterface
{
    public function name(): string
    {
        return 'explain_incident';
    }

    public function title(): string
    {
        return 'Explain incident';
    }

    public function description(): string
    {
        return 'What is behind an incident raised by a SLogger watcher.';
    }

    public function arguments(): array
    {
        return [
            new McpPromptArgument(
                name: 'incident_id',
                description: 'Incident id from list_incidents.',
                required: true
            ),
        ];
    }

    public function text(array $arguments): string
    {
        return implode("\n", [
            sprintf('Explain incident "%s" raised by a SLogger watcher.', $arguments['incident_id'] ?? ''),
            '',
            'Steps:',
            '1. get_incident_events for the watcher, its services and the numbers behind each event.',
            '2. top_trace_groups by ["minute10"] around the first and the last event to see what changed.',
            '3. find_traces in the window of the incident with the filters the watcher implies, then get_trace and '
            . 'get_trace_tree for a few of them.',
            '',
            'Answer with what the incident means, since when it happens, the trace ids behind every claim and what '
            . 'was not checked.',
        ]);
    }
}
