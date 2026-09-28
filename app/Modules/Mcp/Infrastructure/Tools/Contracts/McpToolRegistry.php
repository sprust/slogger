<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

readonly class McpToolRegistry
{
    /**
     * @param McpToolInterface[] $tools
     */
    public function __construct(
        private array $tools
    ) {
    }

    /**
     * @return McpToolInterface[]
     */
    public function all(): array
    {
        return $this->tools;
    }

    public function find(string $name): ?McpToolInterface
    {
        foreach ($this->tools as $tool) {
            if ($tool->name() === $name) {
                return $tool;
            }
        }

        return null;
    }
}
