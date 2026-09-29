<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

readonly class McpRequestHeaders
{
    public function __construct(
        public ?string $protocolVersion,
        public ?string $method,
        public ?string $name
    ) {
    }
}
