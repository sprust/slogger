<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Mcp\Enums\McpIndexStatusEnum;

readonly class McpIndexStatusObject
{
    public function __construct(
        public string $indexId,
        public McpIndexStatusEnum $status,
        public ?float $progress,
        public ?string $error
    ) {
    }
}
