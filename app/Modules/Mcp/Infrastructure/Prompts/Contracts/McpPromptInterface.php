<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts\Contracts;

interface McpPromptInterface
{
    public function name(): string;

    public function title(): string;

    public function description(): string;

    /**
     * @return McpPromptArgument[]
     */
    public function arguments(): array;

    /**
     * @param array<string, string> $arguments
     */
    public function text(array $arguments): string;
}
