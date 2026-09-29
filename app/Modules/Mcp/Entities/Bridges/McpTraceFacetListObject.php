<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Entities\Bridges;

use App\Modules\Trace\Entities\Trace\TraceStringFieldObject;

readonly class McpTraceFacetListObject
{
    /**
     * @param TraceStringFieldObject[] $items
     */
    public function __construct(
        public array $items,
        public bool $truncated
    ) {
    }
}
