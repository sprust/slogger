<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts;

use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptArgument;
use App\Modules\Mcp\Infrastructure\Prompts\Contracts\McpPromptInterface;

readonly class ExplainTracePrompt implements McpPromptInterface
{
    public function name(): string
    {
        return 'explain_trace';
    }

    public function title(): string
    {
        return 'Explain trace';
    }

    public function description(): string
    {
        return 'What a trace did and why it ended the way it did.';
    }

    public function arguments(): array
    {
        return [
            new McpPromptArgument(
                name: 'trace_id',
                description: 'Trace id.',
                required: true
            ),
        ];
    }

    public function text(array $arguments): string
    {
        return implode("\n", [
            sprintf('Explain trace "%s": what it did and why it ended the way it did.', $arguments['trace_id'] ?? ''),
            '',
            'Steps:',
            '1. get_trace for the summary.',
            '2. get_trace_tree to walk the calls it made; find_in_trace_tree for failed or slow calls in a large tree.',
            '3. get_trace_data only for the nodes that explain the result.',
            '',
            'Answer with what happened step by step, the trace ids behind every claim and what was not checked.',
        ]);
    }
}
