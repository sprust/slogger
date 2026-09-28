<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts\Contracts;

readonly class McpPromptRegistry
{
    /**
     * @param McpPromptInterface[] $prompts
     */
    public function __construct(
        private array $prompts
    ) {
    }

    /**
     * @return McpPromptInterface[]
     */
    public function all(): array
    {
        return $this->prompts;
    }

    public function find(string $name): ?McpPromptInterface
    {
        foreach ($this->prompts as $prompt) {
            if ($prompt->name() === $name) {
                return $prompt;
            }
        }

        return null;
    }
}
