<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Protocol;

readonly class McpNotificationMessage
{
    public function __construct(
        public string $method
    ) {
    }
}
