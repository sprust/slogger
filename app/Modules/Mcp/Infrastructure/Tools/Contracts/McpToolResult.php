<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

readonly class McpToolResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public array $data,
        public bool $isError = false,
        public bool $truncate = true
    ) {
    }
}
