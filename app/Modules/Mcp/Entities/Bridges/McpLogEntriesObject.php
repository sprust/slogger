<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpLogEntriesObject
{
    /**
     * @param McpLogEntryObject[] $entries
     */
    public function __construct(
        public bool $indexing,
        public int $total,
        public array $entries
    ) {
    }
}
