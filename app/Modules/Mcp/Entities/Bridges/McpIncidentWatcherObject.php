<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Mcp\Enums\McpWatcherTypeEnum;

readonly class McpIncidentWatcherObject
{
    /** @param int[] $serviceIds */
    public function __construct(
        public int $id,
        public string $name,
        public McpWatcherTypeEnum $type,
        public array $serviceIds
    ) {
    }
}
