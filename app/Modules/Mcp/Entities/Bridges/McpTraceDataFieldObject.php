<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpTraceDataFieldObject
{
    public function __construct(
        public string $key,
        public string|bool|int|float|null $example
    ) {
    }
}
