<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

readonly class McpRequestMessage
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public int|string $id,
        public string $method,
        public array $params
    ) {
    }
}
