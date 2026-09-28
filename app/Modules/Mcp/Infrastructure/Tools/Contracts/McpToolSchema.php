<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

readonly class McpToolSchema
{
    /**
     * @param McpToolProperty[] $properties
     */
    public function __construct(
        public array $properties = []
    ) {
    }
}
