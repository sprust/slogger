<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpIncidentPageObject
{
    /**
     * @param McpIncidentObject[] $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore
    ) {
    }
}
