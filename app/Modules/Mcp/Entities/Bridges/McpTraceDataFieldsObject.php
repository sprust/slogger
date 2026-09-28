<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

readonly class McpTraceDataFieldsObject
{
    /**
     * @param McpTraceDataFieldObject[] $fields
     */
    public function __construct(
        public array $fields,
        public int $tracesCount
    ) {
    }
}
