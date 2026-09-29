<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpIncidentEventPageObject
{
    /**
     * @param McpIncidentEventObject[] $items
     */
    public function __construct(
        public McpIncidentObject $incident,
        public array $items,
        public bool $hasMore
    ) {
    }
}
