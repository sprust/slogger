<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Prompts\Contracts;

readonly class McpPromptArgument
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $required
    ) {
    }
}
